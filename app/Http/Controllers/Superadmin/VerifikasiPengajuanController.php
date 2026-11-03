<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Verifikasi Kelengkapan Pengajuan (UC-05)
 * Aktor  : Superadmin
 * Tabel  : pengajuan, dokumen_pengajuan
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class VerifikasiPengajuanController extends Controller
{
    /**
     * Verifikasi Pengajuan — GET /superadmin/verifikasi-pengajuan
     * Langkah:
     *  - Daftar pengajuan dua jalur, filter status (Semua, Diajukan, Perlu Perbaikan, Lengkap).
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Verifikasi Pengajuan', 'UC-05', 'Superadmin',
            ['Daftar pengajuan dua jalur, filter status (Semua, Diajukan, Perlu Perbaikan, Lengkap).'],
            ['pengajuan', 'dokumen_pengajuan'],
        );
    }

    /**
     * Periksa Pengajuan — GET /superadmin/verifikasi-pengajuan/{pengajuan}
     * Langkah:
     *  - Detail pengajuan, calon, dan checklist dokumen (Sesuai / Tidak Sesuai per berkas).
     */
    public function show(Request $request, Pengajuan $pengajuan): View
    {
        return $this->halamanRancangan(
            'Periksa Pengajuan', 'UC-05', 'Superadmin',
            ['Detail pengajuan, calon, dan checklist dokumen (Sesuai / Tidak Sesuai per berkas).'],
            ['pengajuan', 'dokumen_pengajuan'],
        );
    }

    /**
     * Simpan Pemeriksaan — POST /superadmin/verifikasi-pengajuan/{pengajuan}/periksa
     * Langkah:
     *  - Simpan hasil_pemeriksaan per dokumen (Tidak Sesuai wajib catatan).
     *  - Simpan kesimpulan di pengajuan: status Lengkap / Perlu Perbaikan (+ catatan_perbaikan wajib), id_pemeriksa, waktu_pemeriksaan.
     *  - Notifikasi ke pendaftar (jalur DTI) atau ke Kepala Seksi jika Lengkap.
     */
    public function periksa(Request $request, Pengajuan $pengajuan): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Simpan Pemeriksaan', 'UC-05');
    }
}
