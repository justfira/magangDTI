<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Tugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tugas Magang (Peserta) (UC-07)
 * Aktor  : Peserta Magang
 * Tabel  : tugas, progres_tugas
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class TugasController extends Controller
{
    /**
     * Daftar Tugas — GET /peserta/tugas
     * Langkah:
     *  - Daftar tugas milik peserta login beserta status.
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Daftar Tugas', 'UC-07', 'Peserta Magang',
            ['Daftar tugas milik peserta login beserta status.'],
            ['tugas', 'progres_tugas'],
        );
    }

    /**
     * Detail Tugas — GET /peserta/tugas/{tugas}
     * Langkah:
     *  - Tampilkan detail tugas & riwayat progres beserta hasil review mentor.
     */
    public function show(Request $request, Tugas $tugas): View
    {
        $this->pastikanMilikSendiri($tugas->pesertaMagang);

        return $this->halamanRancangan(
            'Detail Tugas', 'UC-07', 'Peserta Magang',
            ['Tampilkan detail tugas & riwayat progres beserta hasil review mentor.'],
            ['tugas', 'progres_tugas'],
        );
    }

    /**
     * Kirim Progres — POST /peserta/tugas/{tugas}/progres
     * Langkah:
     *  - Validasi catatan_progres wajib, tautan/berkas opsional (A3).
     *  - Simpan progres_tugas baru dengan urutan = urutan terakhir + 1 (perbaikan = progres berikutnya).
     *  - Ubah status tugas menjadi Diserahkan; notifikasi ke mentor.
     */
    public function kirimProgres(Request $request, Tugas $tugas): RedirectResponse
    {
        $this->pastikanMilikSendiri($tugas->pesertaMagang);

        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Kirim Progres', 'UC-07');
    }
}
