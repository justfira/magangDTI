<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengajuan Izin & Presensi Susulan (UC-09 / UC-11)
 * Aktor  : Peserta Magang
 * Tabel  : pengajuan_kehadiran
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class PengajuanKehadiranController extends Controller
{
    /**
     * Riwayat Izin & Presensi Susulan — GET /peserta/pengajuan-kehadiran
     * Langkah:
     *  - Daftar pengajuan milik peserta beserta status verifikasi mentor.
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Riwayat Izin & Presensi Susulan', 'UC-09 / UC-11', 'Peserta Magang',
            ['Daftar pengajuan milik peserta beserta status verifikasi mentor.'],
            ['pengajuan_kehadiran'],
        );
    }

    /**
     * Form Izin / Presensi Susulan — GET /peserta/pengajuan-kehadiran/create
     * Langkah:
     *  - Pilih jenis: IZIN atau SUSULAN.
     */
    public function create(Request $request): View
    {
        return $this->halamanRancangan(
            'Form Izin / Presensi Susulan', 'UC-09 / UC-11', 'Peserta Magang',
            ['Pilih jenis: IZIN atau SUSULAN.'],
            ['pengajuan_kehadiran'],
        );
    }

    /**
     * Kirim Pengajuan — POST /peserta/pengajuan-kehadiran
     * Langkah:
     *  - IZIN: tanggal dalam periode magang & belum ada presensi sah (UC-11 E1, E2); lampiran opsional.
     *  - SUSULAN: tanggal = hari ini, belum hadir, lokasi valid di kantor (UC-09 E1–E3).
     *  - Simpan status Menunggu Verifikasi; notifikasi ke mentor.
     */
    public function store(Request $request): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Kirim Pengajuan', 'UC-09 / UC-11');
    }
}
