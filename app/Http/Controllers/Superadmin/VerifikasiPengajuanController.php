<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use App\Services\AlurPenerimaanService as Alur;
use App\Support\Notifikasi;
use App\Support\Peran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

/**
 * UC-05 (FR-06) Verifikasi Kelengkapan Pengajuan — Superadmin.
 * Tabel : pengajuan, dokumen_pengajuan (+ calon_peserta, keputusan_penerimaan untuk menampilkan hasil)
 *
 * Main flow 1–5 : daftar pengajuan dua jalur -> detail & berkas -> tetapkan Lengkap / Perlu Perbaikan
 *                 -> simpan status + id & waktu pemeriksa.
 * A1            : Perlu Perbaikan wajib catatan; pendaftar DTI diberi notifikasi, institusi melihatnya di halaman
 *                 publik Cek Status (kode + email narahubung) dan mengirim perbaikan dari sana.
 * E1            : gagal simpan -> "Gagal menyimpan, silakan coba lagi", data tidak berubah.
 * E3            : daftar kosong -> "Data tidak tersedia".
 * Langkah 12    : hasil rekomendasi & keputusan per calon terlihat di halaman detail (jalur institusi).
 */
class VerifikasiPengajuanController extends Controller
{
    /** Tab filter sesuai A3: Semua, Diajukan, Perlu Perbaikan, Lengkap (+ Selesai untuk arsip). */
    private const TAB = ['Diajukan', 'Perlu Perbaikan', 'Lengkap', 'Selesai Diputuskan'];

    public function index(Request $request): View
    {
        $jumlah = Pengajuan::selectRaw('status_pengajuan, COUNT(*) as n')->groupBy('status_pengajuan')->pluck('n', 'status_pengajuan');
        $status = in_array($request->query('status'), self::TAB, true) ? $request->query('status') : null;
        $jalur = in_array($request->query('jalur'), Pengajuan::JALUR, true) ? $request->query('jalur') : null;
        $cari = trim((string) $request->query('cari'));

        $pengajuan = Pengajuan::with(['calonPeserta', 'diajukanOleh', 'program'])
            ->withCount('calonPeserta')
            ->when($status, fn ($q) => $q->where('status_pengajuan', $status))
            ->when($jalur, fn ($q) => $q->where('jalur', $jalur))
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('kode_pengajuan', 'like', "%{$cari}%")
                ->orWhere('nama_institusi', 'like', "%{$cari}%")
                ->orWhereHas('calonPeserta', fn ($c) => $c->where('nama_lengkap', 'like', "%{$cari}%"))))
            // Yang perlu ditindak (Diajukan) tampil paling atas, lalu terbaru.
            ->orderByRaw("CASE status_pengajuan WHEN 'Diajukan' THEN 0 WHEN 'Perlu Perbaikan' THEN 1 WHEN 'Lengkap' THEN 2 ELSE 3 END")
            ->orderByDesc('tanggal_pengajuan')->orderByDesc('id_pengajuan')
            ->paginate(10)->withQueryString();

        return view('superadmin.verifikasi.index', [
            'pengajuan' => $pengajuan,
            'jumlah' => $jumlah,
            'total' => (int) $jumlah->sum(),
            'status' => $status,
            'jalur' => $jalur,
            'cari' => $cari,
            'tab' => self::TAB,
        ]);
    }

    public function show(Request $request, Pengajuan $pengajuan): View
    {
        $pengajuan->load([
            'program.syaratDokumenProgram', 'dokumenPengajuan', 'diajukanOleh', 'pemeriksa.pengguna', 'dicatatOleh.pengguna',
            'calonPeserta.keputusanPenerimaan.kepalaSeksi.pengguna', 'calonPeserta.keputusanPenerimaan.direktur.pengguna',
        ]);

        return view('superadmin.verifikasi.show', [
            'pengajuan' => $pengajuan,
            'checklist' => Alur::checklistDokumen($pengajuan),
            'langkah' => Alur::langkahAlur($pengajuan),
            'bisaDiperiksa' => in_array($pengajuan->status_pengajuan, Alur::STATUS_BISA_DIPERIKSA, true),
        ]);
    }

    /** Langkah 4–5 (+A1): simpan hasil per dokumen dan kesimpulan kelengkapan. */
    public function periksa(Request $request, Pengajuan $pengajuan): RedirectResponse
    {
        if (! in_array($pengajuan->status_pengajuan, Alur::STATUS_BISA_DIPERIKSA, true)) {
            return back()->with('gagal', "Pengajuan berstatus \"{$pengajuan->status_pengajuan}\" sudah tidak dapat diperiksa ulang.");
        }

        $pengajuan->load(['program.syaratDokumenProgram', 'dokumenPengajuan', 'calonPeserta']);
        $checklist = Alur::checklistDokumen($pengajuan);
        $idDokumen = $checklist->pluck('dokumen.id_dokumen')->filter()->values()->all();

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:Lengkap,Perlu Perbaikan'],
            'catatan_perbaikan' => ['nullable', 'required_if:status,Perlu Perbaikan', 'string', 'max:2000'],
            'dokumen' => ['array'],
            'dokumen.*.hasil' => ['nullable', 'in:Sesuai,Tidak Sesuai'],
            'dokumen.*.catatan' => ['nullable', 'string', 'max:500', 'required_if:dokumen.*.hasil,Tidak Sesuai'],
        ], [
            'status.required' => 'Pilih hasil pemeriksaan: Lengkap atau Perlu Perbaikan.',
            'catatan_perbaikan.required_if' => 'Catatan perbaikan wajib diisi jika status Perlu Perbaikan.',
            'dokumen.*.catatan.required_if' => 'Catatan wajib diisi untuk dokumen yang Tidak Sesuai.',
            'max' => 'Catatan maksimal :max karakter.',
        ]);

        $validator->after(function ($v) use ($request, $checklist) {
            if ($request->input('status') !== 'Lengkap') {
                return;
            }
            $hasil = $request->input('dokumen', []);
            foreach ($checklist as $baris) {
                $id = $baris['dokumen']?->id_dokumen;
                if ($id && empty($hasil[$id]['hasil'])) {
                    $v->errors()->add("dokumen.{$id}.hasil", 'Tandai dokumen ini Sesuai atau Tidak Sesuai sebelum menyatakan Lengkap.');
                } elseif ($baris['wajib'] && ! $id) {
                    $v->errors()->add('status', "Tidak bisa dinyatakan Lengkap: dokumen \"{$baris['nama']}\" belum diunggah.");
                } elseif ($id && ($hasil[$id]['hasil'] ?? null) === 'Tidak Sesuai') {
                    $v->errors()->add('status', "Tidak bisa dinyatakan Lengkap: dokumen \"{$baris['nama']}\" ditandai Tidak Sesuai.");
                }
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $status = $request->input('status');
        $pegawai = $this->pegawaiLogin();
        if (! $pegawai) {
            return back()->withInput()->with('gagal', 'Akun Anda belum terdaftar sebagai pegawai.');
        }

        try {
            DB::transaction(function () use ($request, $pengajuan, $idDokumen, $status, $pegawai) {
                // Cegah dua admin memeriksa bersamaan: status dicek ulang dengan baris terkunci.
                $terkunci = Pengajuan::whereKey($pengajuan->id_pengajuan)->lockForUpdate()->first();
                if (! in_array($terkunci->status_pengajuan, Alur::STATUS_BISA_DIPERIKSA, true)) {
                    throw new \RuntimeException('Status pengajuan sudah berubah.');
                }

                foreach ($request->input('dokumen', []) as $id => $isi) {
                    if (! in_array((int) $id, $idDokumen, true) || empty($isi['hasil'])) {
                        continue;
                    }
                    $pengajuan->dokumenPengajuan()->whereKey($id)->update([
                        'hasil_pemeriksaan' => $isi['hasil'],
                        'catatan_pemeriksaan' => $isi['hasil'] === 'Tidak Sesuai' ? trim((string) $isi['catatan']) : (trim((string) ($isi['catatan'] ?? '')) ?: null),
                    ]);
                }

                $pengajuan->update([
                    'status_pengajuan' => $status,
                    'catatan_perbaikan' => $status === 'Perlu Perbaikan' ? trim($request->input('catatan_perbaikan')) : null,
                    'id_pemeriksa' => $pegawai->id_pegawai,
                    'waktu_pemeriksaan' => now(),
                    'updated_at' => now(),
                ]);

                $this->kirimNotifikasi($pengajuan->fresh(['calonPeserta']), $status);
            });
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('gagal', Alur::PESAN_GAGAL_SIMPAN);
        }

        Log::channel('aktivitas')->info('pengajuan_diperiksa', [
            'kode' => $pengajuan->kode_pengajuan, 'status' => $status, 'oleh' => auth()->id(),
        ]);

        $pesan = $status === 'Lengkap'
            ? "Pengajuan {$pengajuan->kode_pengajuan} dinyatakan Lengkap dan diteruskan ke Kepala Seksi."
            : "Pengajuan {$pengajuan->kode_pengajuan} ditandai Perlu Perbaikan.".($pengajuan->jalur === 'INSTITUSI' ? ' Institusi dapat melihat catatan dan mengirim perbaikan lewat halaman Cek Status Permohonan.' : ' Pendaftar sudah diberi tahu.');

        return redirect()->route('superadmin.verifikasi.show', $pengajuan)->with('sukses', $pesan);
    }

    private function kirimNotifikasi(Pengajuan $p, string $status): void
    {
        $nama = Alur::namaPengajuan($p);

        if ($status === 'Lengkap') {
            Alur::beritahuPeran(Peran::KEPALA_SEKSI, 'PENGAJUAN_LENGKAP',
                "Pengajuan {$p->kode_pengajuan} ({$nama}) sudah lengkap dan menunggu rekomendasi Anda.", 'pengajuan', $p->id_pengajuan);

            return;
        }

        // A1: catatan perbaikan terlihat oleh pendaftar (jalur DTI punya akun).
        if ($p->jalur === 'DTI' && $p->diajukan_oleh) {
            Notifikasi::kirim($p->diajukan_oleh, 'PERLU_PERBAIKAN',
                "Pengajuan {$p->kode_pengajuan} perlu perbaikan: {$p->catatan_perbaikan}", 'pengajuan', $p->id_pengajuan);
        }
    }
}
