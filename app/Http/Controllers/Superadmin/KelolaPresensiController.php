<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kelola Presensi (UC-10)
 * Aktor  : Superadmin
 * Tabel  : presensi, koreksi_presensi
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class KelolaPresensiController extends Controller
{
    /**
     * Kelola Presensi — GET /superadmin/presensi
     * Langkah:
     *  - Daftar presensi peserta, filter tanggal/peserta.
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Kelola Presensi', 'UC-10', 'Superadmin',
            ['Daftar presensi peserta, filter tanggal/peserta.'],
            ['presensi', 'koreksi_presensi'],
        );
    }

    /**
     * Rekap Kehadiran Bulanan — GET /superadmin/presensi/rekap
     * Langkah:
     *  - Hitung hari hadir per peserta per bulan: presensi REGULER dengan check-in & check-out + SUSULAN/KOREKSI (satu tanggal = satu hari).
     *  - Izin tidak dihitung sebagai hadir (E3 jika data kosong).
     */
    public function rekap(Request $request): View
    {
        return $this->halamanRancangan(
            'Rekap Kehadiran Bulanan', 'UC-10', 'Superadmin',
            ['Hitung hari hadir per peserta per bulan: presensi REGULER dengan check-in & check-out + SUSULAN/KOREKSI (satu tanggal = satu hari).', 'Izin tidak dihitung sebagai hadir (E3 jika data kosong).'],
            ['presensi', 'koreksi_presensi'],
        );
    }

    /**
     * Ekspor CSV — GET /superadmin/presensi/rekap/csv
     * Langkah:
     *  - return response()->streamDownload(...) berisi hasil rekap (E4).
     */
    public function eksporCsv(Request $request): View
    {
        return $this->halamanRancangan(
            'Ekspor CSV', 'UC-10', 'Superadmin',
            ['return response()->streamDownload(...) berisi hasil rekap (E4).'],
            ['presensi', 'koreksi_presensi'],
        );
    }

    /**
     * Koreksi Presensi — GET /superadmin/presensi/{presensi}/koreksi
     * Langkah:
     *  - (lihat use case)
     */
    public function koreksi(Request $request, Presensi $presensi): View
    {
        return $this->halamanRancangan(
            'Koreksi Presensi', 'UC-10', 'Superadmin',
            [],
            ['presensi', 'koreksi_presensi'],
        );
    }

    /**
     * Simpan Koreksi — POST /superadmin/presensi/{presensi}/koreksi
     * Langkah:
     *  - Alasan & bukti wajib (E1); simpan data_sebelum & data_sesudah (JSON) di koreksi_presensi dalam transaksi.
     */
    public function simpanKoreksi(Request $request, Presensi $presensi): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Simpan Koreksi', 'UC-10');
    }
}
