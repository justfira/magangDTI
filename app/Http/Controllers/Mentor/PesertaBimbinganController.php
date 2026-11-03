<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Peserta Bimbingan (UC-06)
 * Aktor  : Mentor DTI
 * Tabel  : peserta_magang
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class PesertaBimbinganController extends Controller
{
    /**
     * Peserta Bimbingan — GET /mentor/peserta-bimbingan
     * Langkah:
     *  - Daftar peserta_magang dengan id_mentor = pegawai login ($request->user()->pegawai->pesertaBimbingan).
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Peserta Bimbingan', 'UC-06', 'Mentor DTI',
            ['Daftar peserta_magang dengan id_mentor = pegawai login ($request->user()->pegawai->pesertaBimbingan).'],
            ['peserta_magang'],
        );
    }
}
