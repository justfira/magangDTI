<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\PenyelesaianMagang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sertifikat (Superadmin) (UC-15)
 * Aktor  : Superadmin
 * Tabel  : penyelesaian_magang
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class SertifikatController extends Controller
{
    /**
     * Sertifikat Magang — GET /superadmin/sertifikat
     * Langkah:
     *  - Daftar penyelesaian berstatus Disetujui & status unggah sertifikat.
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Sertifikat Magang', 'UC-15', 'Superadmin',
            ['Daftar penyelesaian berstatus Disetujui & status unggah sertifikat.'],
            ['penyelesaian_magang'],
        );
    }

    /**
     * Unggah Sertifikat — POST /superadmin/sertifikat/{penyelesaian}/unggah
     * Langkah:
     *  - Tolak jika keputusan bukan Disetujui (A1) — dijaga juga oleh CHECK di database.
     *  - Validasi PDF; simpan ke storage; isi nomor_sertifikat, path_sertifikat, id_pengunggah, waktu_unggah; notifikasi peserta.
     */
    public function unggah(Request $request, PenyelesaianMagang $penyelesaian): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Unggah Sertifikat', 'UC-15');
    }
}
