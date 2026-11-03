<?php

namespace App\Http\Controllers\Direktur;

use App\Http\Controllers\Controller;
use App\Models\KeputusanPenerimaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Persetujuan Penerimaan (UC-05)
 * Aktor  : Direktur DTI
 * Tabel  : keputusan_penerimaan
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class KeputusanPenerimaanController extends Controller
{
    /**
     * Persetujuan Penerimaan — GET /direktur/keputusan-penerimaan
     * Langkah:
     *  - Daftar rekomendasi yang belum diputuskan (keputusan IS NULL).
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Persetujuan Penerimaan', 'UC-05', 'Direktur DTI',
            ['Daftar rekomendasi yang belum diputuskan (keputusan IS NULL).'],
            ['keputusan_penerimaan'],
        );
    }

    /**
     * Simpan Keputusan Penerimaan — PUT /direktur/keputusan-penerimaan/{keputusan}
     * Langkah:
     *  - Isi keputusan (hasil AKHIR: Diterima / Ditolak), id_direktur, waktu_keputusan; notifikasi pendaftar / Superadmin.
     */
    public function update(Request $request, KeputusanPenerimaan $keputusan): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Simpan Keputusan Penerimaan', 'UC-05');
    }
}
