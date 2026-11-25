<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\DokumenPengajuan;
use App\Models\Pengguna;
use App\Models\Pengajuan;
use App\Support\Notifikasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Verifikasi Kelengkapan Pengajuan (UC-05)
 * Aktor  : Superadmin
 * Tabel  : pengajuan, dokumen_pengajuan
 */
class VerifikasiPengajuanController extends Controller
{
    /** Disk privat tempat berkas pengajuan disimpan. */
    private const DISK = 'local';

    /**
     * Verifikasi Pengajuan — GET /superadmin/verifikasi-pengajuan
     * Daftar pengajuan dua jalur (DTI & INSTITUSI) dengan filter status, jalur, dan pencarian.
     * Pengurutan: status aktif (Diajukan, Perlu Perbaikan) didahulukan, lalu terbaru.
     */
    public function index(Request $request): View
    {
        $cari   = $request->string('cari')->trim()->value();
        $jalur  = $request->input('jalur');
        $status = $request->input('status');

        $jalurValid  = in_array($jalur,  Pengajuan::JALUR,  true) ? $jalur  : null;
        $statusValid = in_array($status, Pengajuan::STATUS, true) ? $status : null;

        $pengajuan = Pengajuan::with([
                'program:id_program,nama_program',
                'diajukanOleh:id_pengguna,nama_lengkap',
                'pemeriksa.pengguna:id_pengguna,nama_lengkap',
            ])
            ->withCount('calonPeserta')
            ->when($jalurValid, fn (Builder $q, string $j) => $q->where('jalur', $j))
            ->when($statusValid, fn (Builder $q, string $s) => $q->where('status_pengajuan', $s))
            ->when($cari !== '', fn (Builder $q) => $q->where(fn (Builder $qq) =>
                $qq->where('kode_pengajuan', 'like', "%{$cari}%")
                   ->orWhere('nama_institusi', 'like', "%{$cari}%")
                   ->orWhereHas('diajukanOleh', fn (Builder $qqq) =>
                       $qqq->where('nama_lengkap', 'like', "%{$cari}%")
                   )
            ))
            ->orderByRaw("
                FIELD(status_pengajuan,
                    'Diajukan',
                    'Perlu Perbaikan',
                    'Lengkap',
                    'Selesai Diputuskan'
                )
            ")
            ->orderByDesc('tanggal_pengajuan')
            ->paginate(15)
            ->withQueryString();

        $jumlahPerlu = Pengajuan::whereIn('status_pengajuan', ['Diajukan', 'Perlu Perbaikan'])->count();

        return view('superadmin.verifikasi.index', [
            'pengajuan'    => $pengajuan,
            'daftarJalur'  => Pengajuan::JALUR,
            'daftarStatus' => Pengajuan::STATUS,
            'jumlahPerlu'  => $jumlahPerlu,
            'cari'         => $cari,
            'jalurAktif'   => $jalurValid,
            'statusAktif'  => $statusValid,
        ]);
    }

    /**
     * Detail Pengajuan — GET /superadmin/verifikasi-pengajuan/{pengajuan}
     * Tampilkan detail pengajuan, data calon peserta, dan daftar dokumen dengan status pemeriksaan.
     */
    public function show(Request $request, Pengajuan $pengajuan): View
    {
        $pengajuan->load([
            'program.syaratDokumenProgram',
            'diajukanOleh:id_pengguna,nama_lengkap,email',
            'dicatatOleh.pengguna:id_pengguna,nama_lengkap',
            'pemeriksa.pengguna:id_pengguna,nama_lengkap',
            'calonPeserta',
            'dokumenPengajuan.syarat',
        ]);

        return view('superadmin.verifikasi.show', compact('pengajuan'));
    }

    /**
     * Unduh Dokumen — GET /superadmin/verifikasi-pengajuan/{pengajuan}/dokumen/{dokumen}
     * Melayani berkas dari disk privat setelah memverifikasi bahwa dokumen milik pengajuan ini.
     */
    public function unduhDokumen(Pengajuan $pengajuan, DokumenPengajuan $dokumen): Response
    {
        abort_if(
            $dokumen->id_pengajuan !== $pengajuan->id_pengajuan,
            403,
            'Dokumen tidak terkait dengan pengajuan ini.'
        );

        $disk = Storage::disk(self::DISK);
        abort_unless($disk->exists($dokumen->path_berkas), 404, 'File dokumen tidak ditemukan.');

        return $disk->response($dokumen->path_berkas, $dokumen->nama_berkas);
    }

    /**
     * Simpan Pemeriksaan — POST /superadmin/verifikasi-pengajuan/{pengajuan}/periksa
     * Validasi hasil per dokumen -> update dokumen -> update status pengajuan -> notifikasi.
     */
    public function periksa(Request $request, Pengajuan $pengajuan): RedirectResponse
    {
        // Guard: pengajuan sudah final tidak boleh diperiksa ulang
        abort_if(
            $pengajuan->status_pengajuan === 'Selesai Diputuskan',
            403,
            'Pengajuan ini sudah diputuskan dan tidak dapat diperiksa ulang.'
        );

        $pemeriksa = $this->pegawaiLogin();
        abort_if($pemeriksa === null, 403, 'Akun Anda tidak terdaftar sebagai pegawai.');

        $pengajuan->load(['dokumenPengajuan', 'calonPeserta', 'diajukanOleh']);

        // Bangun aturan validasi dinamis per dokumen
        $aturan = [];
        $pesan  = [];
        foreach ($pengajuan->dokumenPengajuan as $dok) {
            $key = $dok->id_dokumen;
            $aturan["hasil.{$key}"]   = ['required', Rule::in(DokumenPengajuan::HASIL)];
            $aturan["catatan.{$key}"] = ['nullable', 'string', 'max:1000'];
            $pesan["hasil.{$key}.required"] = "Hasil pemeriksaan wajib dipilih untuk dokumen \u201c{$dok->jenis_dokumen}\u201d.";
            $pesan["hasil.{$key}.in"]       = "Nilai hasil tidak valid untuk dokumen \u201c{$dok->jenis_dokumen}\u201d.";
            $pesan["catatan.{$key}.max"]    = 'Catatan pemeriksaan maksimal 1.000 karakter.';
        }
        $aturan['kesimpulan']        = ['required', Rule::in(['Lengkap', 'Perlu Perbaikan'])];
        $aturan['catatan_perbaikan'] = [
            'nullable',
            'required_if:kesimpulan,Perlu Perbaikan',
            'string',
            'max:2000',
        ];
        $pesan['kesimpulan.required']           = 'Kesimpulan pemeriksaan wajib dipilih.';
        $pesan['kesimpulan.in']                 = 'Nilai kesimpulan tidak valid.';
        $pesan['catatan_perbaikan.required_if'] = 'Catatan perbaikan wajib diisi jika kesimpulan Perlu Perbaikan.';
        $pesan['catatan_perbaikan.max']         = 'Catatan perbaikan maksimal 2.000 karakter.';

        $data = $request->validate($aturan, $pesan);

        // Validasi silang: catatan wajib bila dokumen Tidak Sesuai
        $crossErrors = [];
        foreach ($pengajuan->dokumenPengajuan as $dok) {
            $key     = $dok->id_dokumen;
            $hasil   = $data['hasil'][$key]   ?? null;
            $catatan = trim((string) ($data['catatan'][$key] ?? ''));
            if ($hasil === 'Tidak Sesuai' && $catatan === '') {
                $crossErrors["catatan.{$key}"] =
                    "Catatan wajib diisi karena dokumen \u201c{$dok->jenis_dokumen}\u201d dinyatakan Tidak Sesuai.";
            }
        }
        if ($crossErrors) {
            return back()->withInput()->withErrors($crossErrors);
        }

        // Simpan dalam transaksi
        try {
            DB::transaction(function () use ($data, $pengajuan, $pemeriksa) {
                foreach ($pengajuan->dokumenPengajuan as $dok) {
                    $key = $dok->id_dokumen;
                    $dok->update([
                        'hasil_pemeriksaan'   => $data['hasil'][$key],
                        'catatan_pemeriksaan' => $data['catatan'][$key] ?? null,
                    ]);
                }

                $pengajuan->update([
                    'status_pengajuan'  => $data['kesimpulan'],
                    'catatan_perbaikan' => $data['kesimpulan'] === 'Perlu Perbaikan'
                                           ? $data['catatan_perbaikan']
                                           : null,
                    'id_pemeriksa'      => $pemeriksa->id_pegawai,
                    'waktu_pemeriksaan' => now(),
                ]);
            });
        } catch (Throwable $e) {
            Log::error('verifikasi_pengajuan_gagal', [
                'id_pengajuan' => $pengajuan->id_pengajuan,
                'error'        => $e->getMessage(),
            ]);
            return back()->withInput()
                ->with('gagal', 'Penyimpanan gagal. Silakan coba lagi atau hubungi administrator.');
        }

        // Kirim notifikasi setelah transaksi berhasil
        $this->kirimNotifikasi($pengajuan->fresh(['diajukanOleh']));

        Log::channel('aktivitas')->info('pengajuan_diperiksa', [
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'status_baru'  => $pengajuan->status_pengajuan,
            'oleh'         => auth()->id(),
        ]);

        return redirect()
            ->route('superadmin.verifikasi.index')
            ->with('sukses', "Pemeriksaan pengajuan {$pengajuan->kode_pengajuan} berhasil disimpan.");
    }

    // -------------------------------------------------------------------------
    // Helper privat
    // -------------------------------------------------------------------------

    /**
     * Kirim notifikasi setelah pemeriksaan selesai.
     * - Lengkap         : notifikasi ke semua Kepala Seksi aktif + pendaftar DTI.
     * - Perlu Perbaikan : notifikasi ke pendaftar DTI saja
     *   (INSTITUSI dicatat Superadmin, tidak ada akun pendaftar yang perlu diberi tahu).
     */
    private function kirimNotifikasi(Pengajuan $pengajuan): void
    {
        $kode = $pengajuan->kode_pengajuan;

        if ($pengajuan->status_pengajuan === 'Lengkap') {
            Pengguna::where('status_akun', 'Aktif')
                ->whereHas('peran', fn (Builder $q) => $q->where('kode_peran', 'KEPALA_SEKSI'))
                ->each(fn (Pengguna $kasi) => Notifikasi::kirim(
                    $kasi->id_pengguna,
                    'PENGAJUAN_LENGKAP',
                    "Pengajuan {$kode} dinyatakan lengkap dan siap direview.",
                    'pengajuan',
                    $pengajuan->id_pengajuan,
                ));

            if ($idPendaftar = $pengajuan->diajukan_oleh) {
                Notifikasi::kirim(
                    $idPendaftar,
                    'PENGAJUAN_LENGKAP',
                    "Pengajuan Anda ({$kode}) dinyatakan lengkap. Mohon tunggu keputusan selanjutnya.",
                    'pengajuan',
                    $pengajuan->id_pengajuan,
                );
            }
        } else {
            if ($idPendaftar = $pengajuan->diajukan_oleh) {
                $catatan = $pengajuan->catatan_perbaikan
                    ? " Catatan: {$pengajuan->catatan_perbaikan}"
                    : '';
                Notifikasi::kirim(
                    $idPendaftar,
                    'PENGAJUAN_PERLU_PERBAIKAN',
                    "Pengajuan Anda ({$kode}) memerlukan perbaikan dokumen.{$catatan}",
                    'pengajuan',
                    $pengajuan->id_pengajuan,
                );
            }
        }
    }
}
