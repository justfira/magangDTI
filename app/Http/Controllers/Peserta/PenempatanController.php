<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Informasi Penempatan (UC-06)
 * Aktor  : Peserta Magang
 * Tabel  : peserta_magang, seksi, pegawai
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class PenempatanController extends Controller
{
    /**
     * Informasi Penempatan — GET /peserta/penempatan
     * Langkah:
     *  - Ambil peserta_magang milik pengguna login, tampilkan seksi & mentor.
     */
    public function show(Request $request): View
    {
        return $this->halamanRancangan(
            'Informasi Penempatan', 'UC-06', 'Peserta Magang',
            ['Ambil peserta_magang milik pengguna login, tampilkan seksi & mentor.'],
            ['peserta_magang', 'seksi', 'pegawai'],
        );
    }
}
