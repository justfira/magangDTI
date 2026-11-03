<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\Logbook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Periksa Logbook (UC-12)
 * Aktor  : Mentor DTI
 * Tabel  : logbook
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class PeriksaLogbookController extends Controller
{
    /**
     * Periksa Logbook — GET /mentor/logbook
     * Langkah:
     *  - Daftar logbook peserta bimbingan berstatus Dikirim.
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Periksa Logbook', 'UC-12', 'Mentor DTI',
            ['Daftar logbook peserta bimbingan berstatus Dikirim.'],
            ['logbook'],
        );
    }

    /**
     * Simpan Pemeriksaan Logbook — POST /mentor/logbook/{logbook}/periksa
     * Langkah:
     *  - Disetujui / Perlu Perbaikan (+ catatan_mentor wajib), isi waktu_periksa; notifikasi ke peserta.
     */
    public function periksa(Request $request, Logbook $logbook): RedirectResponse
    {
        $this->pastikanBimbingan($logbook->pesertaMagang);

        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Simpan Pemeriksaan Logbook', 'UC-12');
    }
}
