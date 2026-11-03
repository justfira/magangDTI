<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\PenyelesaianMagang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sertifikat (Peserta) (UC-15)
 * Aktor  : Peserta Magang
 * Tabel  : penyelesaian_magang
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class SertifikatController extends Controller
{
    /**
     * Sertifikat Magang — GET /peserta/sertifikat
     * Langkah:
     *  - Tampilkan sertifikat milik sendiri jika path_sertifikat sudah ada (A2).
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Sertifikat Magang', 'UC-15', 'Peserta Magang',
            ['Tampilkan sertifikat milik sendiri jika path_sertifikat sudah ada (A2).'],
            ['penyelesaian_magang'],
        );
    }

    /**
     * Unduh Sertifikat — GET /peserta/sertifikat/{penyelesaian}/unduh
     * Langkah:
     *  - Pastikan milik sendiri, lalu return Storage::download(path_sertifikat) (E2).
     */
    public function unduh(Request $request, PenyelesaianMagang $penyelesaian): View
    {
        $this->pastikanMilikSendiri($penyelesaian->pesertaMagang);

        return $this->halamanRancangan(
            'Unduh Sertifikat', 'UC-15', 'Peserta Magang',
            ['Pastikan milik sendiri, lalu return Storage::download(path_sertifikat) (E2).'],
            ['penyelesaian_magang'],
        );
    }
}
