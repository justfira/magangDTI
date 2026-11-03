<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\PesertaMagang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Evaluasi Peserta (UC-13)
 * Aktor  : Mentor DTI
 * Tabel  : evaluasi_peserta
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class EvaluasiController extends Controller
{
    /**
     * Evaluasi Peserta — GET /mentor/evaluasi
     * Langkah:
     *  - Daftar peserta bimbingan & status evaluasinya.
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Evaluasi Peserta', 'UC-13', 'Mentor DTI',
            ['Daftar peserta bimbingan & status evaluasinya.'],
            ['evaluasi_peserta'],
        );
    }

    /**
     * Catat Evaluasi — GET /mentor/evaluasi/{peserta}/edit
     * Langkah:
     *  - Jalur DTI: Memenuhi / Belum Memenuhi + catatan. Jalur INSTITUSI: Belum/Sudah Dinilai + unggah dokumen.
     */
    public function edit(Request $request, PesertaMagang $peserta): View
    {
        $this->pastikanBimbingan($peserta);

        return $this->halamanRancangan(
            'Catat Evaluasi', 'UC-13', 'Mentor DTI',
            ['Jalur DTI: Memenuhi / Belum Memenuhi + catatan. Jalur INSTITUSI: Belum/Sudah Dinilai + unggah dokumen.'],
            ['evaluasi_peserta'],
        );
    }

    /**
     * Simpan Evaluasi — PUT /mentor/evaluasi/{peserta}
     * Langkah:
     *  - updateOrCreate evaluasi_peserta (satu per peserta).
     */
    public function update(Request $request, PesertaMagang $peserta): RedirectResponse
    {
        $this->pastikanBimbingan($peserta);

        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Simpan Evaluasi', 'UC-13');
    }
}
