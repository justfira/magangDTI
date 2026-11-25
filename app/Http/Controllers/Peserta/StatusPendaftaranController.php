<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Status & Perbaikan Pendaftaran (UC-03 / UC-05)
 * Aktor  : Calon Peserta
 * Tabel  : pengajuan, dokumen_pengajuan, keputusan_penerimaan
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class StatusPendaftaranController extends Controller
{
    /**
     * Status Pendaftaran — GET /peserta/pendaftaran   (UC-05 langkah 12 & A1, sisi pendaftar)
     *  - Tracker status pengajuan milik pengguna login (pengajuanSaya).
     *  - Jika status Perlu Perbaikan: tampilkan catatan_perbaikan & dokumen yang Tidak Sesuai.
     *  - Jika sudah diputuskan Direktur: tampilkan hasil Diterima / Ditolak.
     * Form & kirim perbaikan (edit/update di bawah) tetap bagian UC-03 A4.
     */
    public function index(Request $request): View
    {
        $pengajuan = $request->user()->pengajuanSaya()
            ->with(['program.syaratDokumenProgram', 'dokumenPengajuan', 'calonPeserta.keputusanPenerimaan', 'pemeriksa.pengguna'])
            ->orderByDesc('tanggal_pengajuan')
            ->get();

        return view('peserta.pendaftaran.index', compact('pengajuan'));
    }

    /**
     * Form Perbaikan Pendaftaran — GET /peserta/pendaftaran/{pengajuan}/perbaikan
     * Langkah:
     *  - Hanya untuk pengajuan milik sendiri yang berstatus Perlu Perbaikan (A4).
     */
    public function edit(Request $request, Pengajuan $pengajuan): View
    {
        abort_unless($pengajuan->diajukan_oleh === $request->user()->id_pengguna, 403, 'Anda tidak memiliki akses ke pengajuan ini.');

        return $this->halamanRancangan(
            'Form Perbaikan Pendaftaran', 'UC-03 / UC-05', 'Calon Peserta',
            ['Hanya untuk pengajuan milik sendiri yang berstatus Perlu Perbaikan (A4).'],
            ['pengajuan', 'dokumen_pengajuan', 'keputusan_penerimaan'],
        );
    }

    /**
     * Kirim Perbaikan — PUT /peserta/pendaftaran/{pengajuan}/perbaikan
     * Langkah:
     *  - Unggah ulang berkas = baris dokumen_pengajuan baru (riwayat tetap ada).
     *  - Ubah status pengajuan kembali menjadi Diajukan; notifikasi ke Superadmin.
     */
    public function update(Request $request, Pengajuan $pengajuan): RedirectResponse
    {
        abort_unless($pengajuan->diajukan_oleh === $request->user()->id_pengguna, 403, 'Anda tidak memiliki akses ke pengajuan ini.');

        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Kirim Perbaikan', 'UC-03 / UC-05');
    }
}
