<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Monitoring Program Magang (UC-16)
 * Aktor  : Superadmin, Kepala Seksi, Direktur
 * Tabel  : peserta_magang, presensi, penyelesaian_magang
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class MonitoringController extends Controller
{
    /**
     * Monitoring Program Magang — GET /monitoring
     * Langkah:
     *  - Ringkasan peserta, penempatan, kehadiran, status penyelesaian (read-only).
     *  - Kepala Seksi hanya melihat seksinya sendiri (A2: cakupan akses).
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Monitoring Program Magang', 'UC-16', 'Superadmin, Kepala Seksi, Direktur',
            ['Ringkasan peserta, penempatan, kehadiran, status penyelesaian (read-only).', 'Kepala Seksi hanya melihat seksinya sendiri (A2: cakupan akses).'],
            ['peserta_magang', 'presensi', 'penyelesaian_magang'],
        );
    }
}
