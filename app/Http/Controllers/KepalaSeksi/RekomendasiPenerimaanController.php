<?php

namespace App\Http\Controllers\KepalaSeksi;

use App\Http\Controllers\Controller;
use App\Models\CalonPeserta;
use App\Models\KeputusanPenerimaan;
use App\Services\AlurPenerimaanService as Alur;
use App\Support\Peran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * UC-05 (FR-07) Rekomendasi Penerimaan — Kepala Seksi DTI.
 * Tabel : calon_peserta, keputusan_penerimaan
 *
 * Main flow 6–8 : daftar calon dari pengajuan berstatus Lengkap -> detail -> rekomendasi Terima/Tolak + catatan
 *                 -> simpan rekomendasi + identitas Kepala Seksi.
 * A2            : pengajuan belum Lengkap -> ditolak "Pengajuan belum dinyatakan lengkap".
 * E1            : gagal simpan -> "Gagal menyimpan, silakan coba lagi".
 *
 * Rekomendasi diberikan PER CALON, karena satu permohonan institusi bisa memuat banyak peserta.
 * Detail ditampilkan di panel kanan halaman yang sama (?calon=ID), sesuai mockup A3.
 */
class RekomendasiPenerimaanController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'riwayat' ? 'riwayat' : 'menunggu';
        $cari = trim((string) $request->query('cari'));

        $dasarMenunggu = CalonPeserta::whereHas('pengajuan', fn ($q) => $q->where('status_pengajuan', 'Lengkap'))
            ->whereDoesntHave('keputusanPenerimaan');
        $dasarRiwayat = CalonPeserta::whereHas('keputusanPenerimaan');

        $ringkasan = ['menunggu' => (clone $dasarMenunggu)->count(), 'riwayat' => (clone $dasarRiwayat)->count()];

        $daftar = ($tab === 'riwayat' ? $dasarRiwayat : $dasarMenunggu)
            ->with(['pengajuan', 'keputusanPenerimaan'])
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nama_lengkap', 'like', "%{$cari}%")
                ->orWhere('institusi_asal', 'like', "%{$cari}%")
                ->orWhereHas('pengajuan', fn ($p) => $p->where('kode_pengajuan', 'like', "%{$cari}%"))))
            ->orderByDesc('id_calon')
            ->paginate(10)->withQueryString();

        $terpilih = null;
        if ($request->filled('calon')) {
            $terpilih = CalonPeserta::with([
                'pengajuan.program.syaratDokumenProgram', 'pengajuan.dokumenPengajuan', 'pengajuan.pemeriksa.pengguna',
                'pengajuan.calonPeserta.keputusanPenerimaan', 'keputusanPenerimaan.kepalaSeksi.pengguna', 'keputusanPenerimaan.direktur.pengguna',
            ])->find($request->integer('calon'));
        }

        return view('kepala-seksi.rekomendasi.index', [
            'daftar' => $daftar,
            'tab' => $tab,
            'cari' => $cari,
            'ringkasan' => $ringkasan,
            'terpilih' => $terpilih,
            'checklist' => $terpilih ? Alur::checklistDokumen($terpilih->pengajuan) : collect(),
        ]);
    }

    public function store(Request $request, CalonPeserta $calon): RedirectResponse
    {
        $kembali = redirect()->route('kasi.rekomendasi.index', ['calon' => $calon->id_calon]);

        // A2 — server tetap menolak walau tombol disembunyikan di tampilan.
        if ($calon->pengajuan?->status_pengajuan !== 'Lengkap') {
            return $kembali->with('gagal', Alur::PESAN_BELUM_LENGKAP);
        }
        if ($calon->keputusanPenerimaan()->exists()) {
            return $kembali->with('gagal', 'Calon ini sudah direkomendasikan sebelumnya.');
        }

        $data = $request->validate([
            'rekomendasi' => ['required', 'in:Terima,Tolak'],
            'catatan_rekomendasi' => ['required', 'string', 'max:1000'],
        ], [
            'rekomendasi.required' => 'Pilih rekomendasi Terima atau Tolak.',
            'rekomendasi.in' => 'Rekomendasi harus Terima atau Tolak.',
            'catatan_rekomendasi.required' => 'Catatan rekomendasi wajib diisi.',
            'catatan_rekomendasi.max' => 'Catatan maksimal 1000 karakter.',
        ]);

        $pegawai = $this->pegawaiLogin();
        if (! $pegawai) {
            return $kembali->with('gagal', 'Akun Anda belum terdaftar sebagai pegawai.');
        }

        try {
            DB::transaction(function () use ($calon, $data, $pegawai) {
                KeputusanPenerimaan::create([
                    'id_calon' => $calon->id_calon,
                    'id_kepala_seksi' => $pegawai->id_pegawai,
                    'rekomendasi' => $data['rekomendasi'],
                    'catatan_rekomendasi' => trim($data['catatan_rekomendasi']),
                    'waktu_rekomendasi' => now(),
                ]);

                Alur::beritahuPeran(Peran::DIREKTUR, 'REKOMENDASI_BARU',
                    "Rekomendasi {$data['rekomendasi']} untuk {$calon->nama_lengkap} ({$calon->pengajuan->kode_pengajuan}) menunggu keputusan Anda.",
                    'calon_peserta', $calon->id_calon);
            });
        } catch (Throwable $e) {
            report($e);   // termasuk unique id_calon bila dua kasi menyimpan bersamaan

            return $kembali->withInput()->with('gagal', Alur::PESAN_GAGAL_SIMPAN);
        }

        Log::channel('aktivitas')->info('rekomendasi_penerimaan', [
            'calon' => $calon->id_calon, 'rekomendasi' => $data['rekomendasi'], 'oleh' => auth()->id(),
        ]);

        return redirect()->route('kasi.rekomendasi.index')
            ->with('sukses', "Rekomendasi {$data['rekomendasi']} untuk {$calon->nama_lengkap} tersimpan dan diteruskan ke Direktur.");
    }
}
