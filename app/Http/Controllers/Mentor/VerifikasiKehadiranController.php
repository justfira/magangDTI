<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\PengajuanKehadiran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Verifikasi Izin & Presensi Susulan (UC-09 / UC-11)
 * Aktor  : Mentor DTI
 * Tabel  : pengajuan_kehadiran, presensi
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class VerifikasiKehadiranController extends Controller
{
    /**
     * Verifikasi Izin & Presensi Susulan — GET /mentor/verifikasi-kehadiran
     * Langkah:
     *  - Daftar pengajuan dari peserta bimbingan, filter status.
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Verifikasi Izin & Presensi Susulan', 'UC-09 / UC-11', 'Mentor DTI',
            ['Daftar pengajuan dari peserta bimbingan, filter status.'],
            ['pengajuan_kehadiran', 'presensi'],
        );
    }

    /**
     * Setujui Pengajuan — POST /mentor/verifikasi-kehadiran/{pengajuanKehadiran}/setujui
     * Langkah:
     *  - Cek ulang: peserta Aktif & tanggal belum punya presensi sah (UC-11 E2, E3).
     *  - SUSULAN disetujui → buat 1 baris presensi (sumber SUSULAN, id_pengajuan_kehadiran) dalam transaksi.
     *  - Notifikasi ke peserta.
     */
    public function setujui(Request $request, PengajuanKehadiran $pengajuanKehadiran): RedirectResponse
    {
        $this->pastikanBimbingan($pengajuanKehadiran->pesertaMagang);

        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Setujui Pengajuan', 'UC-09 / UC-11');
    }

    /**
     * Tolak Pengajuan — POST /mentor/verifikasi-kehadiran/{pengajuanKehadiran}/tolak
     * Langkah:
     *  - Catatan keputusan wajib; notifikasi ke peserta.
     */
    public function tolak(Request $request, PengajuanKehadiran $pengajuanKehadiran): RedirectResponse
    {
        $this->pastikanBimbingan($pengajuanKehadiran->pesertaMagang);

        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Tolak Pengajuan', 'UC-09 / UC-11');
    }
}
