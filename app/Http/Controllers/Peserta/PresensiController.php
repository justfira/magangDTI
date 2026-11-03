<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Presensi Reguler (UC-08)
 * Aktor  : Peserta Magang
 * Tabel  : presensi
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class PresensiController extends Controller
{
    /**
     * Presensi Harian — GET /peserta/presensi
     * Langkah:
     *  - Tampilkan status presensi hari ini & riwayat.
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Presensi Harian', 'UC-08', 'Peserta Magang',
            ['Tampilkan status presensi hari ini & riwayat.'],
            ['presensi'],
        );
    }

    /**
     * Check-in — POST /peserta/presensi/check-in
     * Langkah:
     *  - Pastikan peserta berstatus Aktif (E3).
     *  - Validasi lokasi pakai App\\Services\\GeofenceService (radius & akurasi dari config/presensi.php) (E2).
     *  - Tolak jika sudah check-in hari ini (A1) — dijamin juga oleh UNIQUE (id_peserta_magang, tanggal).
     *  - Catat waktu_checkin = now() (waktu server).
     */
    public function checkIn(Request $request): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Check-in', 'UC-08');
    }

    /**
     * Check-out — POST /peserta/presensi/check-out
     * Langkah:
     *  - Tolak jika belum check-in hari ini (E4); validasi lokasi; isi waktu_checkout pada baris yang sama.
     */
    public function checkOut(Request $request): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Check-out', 'UC-08');
    }
}
