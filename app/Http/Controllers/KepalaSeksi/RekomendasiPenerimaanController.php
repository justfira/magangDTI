<?php

namespace App\Http\Controllers\KepalaSeksi;

use App\Http\Controllers\Controller;
use App\Models\CalonPeserta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Rekomendasi Penerimaan (UC-05)
 * Aktor  : Kepala Seksi DTI
 * Tabel  : calon_peserta, keputusan_penerimaan
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class RekomendasiPenerimaanController extends Controller
{
    /**
     * Rekomendasi Penerimaan — GET /kepala-seksi/rekomendasi-penerimaan
     * Langkah:
     *  - Hanya calon dari pengajuan berstatus Lengkap yang belum direkomendasikan.
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Rekomendasi Penerimaan', 'UC-05', 'Kepala Seksi DTI',
            ['Hanya calon dari pengajuan berstatus Lengkap yang belum direkomendasikan.'],
            ['calon_peserta', 'keputusan_penerimaan'],
        );
    }

    /**
     * Simpan Rekomendasi — POST /kepala-seksi/rekomendasi-penerimaan/{calon}
     * Langkah:
     *  - Tolak jika pengajuan belum Lengkap (A2).
     *  - Buat keputusan_penerimaan (id_calon, id_kepala_seksi, rekomendasi Terima/Tolak, catatan); notifikasi ke Direktur.
     */
    public function store(Request $request, CalonPeserta $calon): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Simpan Rekomendasi', 'UC-05');
    }
}
