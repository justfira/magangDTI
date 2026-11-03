<?php

namespace App\Http\Controllers\Direktur;

use App\Http\Controllers\Controller;
use App\Models\PenyelesaianMagang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Persetujuan Penyelesaian (UC-14)
 * Aktor  : Direktur DTI
 * Tabel  : penyelesaian_magang
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class KeputusanPenyelesaianController extends Controller
{
    /**
     * Persetujuan Penyelesaian — GET /direktur/keputusan-penyelesaian
     * Langkah:
     *  - Daftar rekomendasi penyelesaian yang belum diputuskan.
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Persetujuan Penyelesaian', 'UC-14', 'Direktur DTI',
            ['Daftar rekomendasi penyelesaian yang belum diputuskan.'],
            ['penyelesaian_magang'],
        );
    }

    /**
     * Simpan Keputusan Penyelesaian — PUT /direktur/keputusan-penyelesaian/{penyelesaian}
     * Langkah:
     *  - Disetujui / Ditolak; jika Disetujui ubah peserta_magang.status_magang = Selesai; notifikasi Superadmin (unggah sertifikat).
     */
    public function update(Request $request, PenyelesaianMagang $penyelesaian): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Simpan Keputusan Penyelesaian', 'UC-14');
    }
}
