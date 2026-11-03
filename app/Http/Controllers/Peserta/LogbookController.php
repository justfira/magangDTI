<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Logbook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Logbook Harian (Peserta) (UC-12)
 * Aktor  : Peserta Magang
 * Tabel  : logbook
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class LogbookController extends Controller
{
    /**
     * Logbook Harian — GET /peserta/logbook
     * Langkah:
     *  - Daftar logbook & hari hadir yang belum punya logbook.
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Logbook Harian', 'UC-12', 'Peserta Magang',
            ['Daftar logbook & hari hadir yang belum punya logbook.'],
            ['logbook'],
        );
    }

    /**
     * Isi Logbook — GET /peserta/logbook/create
     * Langkah:
     *  - (lihat use case)
     */
    public function create(Request $request): View
    {
        return $this->halamanRancangan(
            'Isi Logbook', 'UC-12', 'Peserta Magang',
            [],
            ['logbook'],
        );
    }

    /**
     * Simpan Logbook — POST /peserta/logbook
     * Langkah:
     *  - Satu logbook per peserta per tanggal (UNIQUE) (A2).
     *  - Notifikasi ke mentor.
     */
    public function store(Request $request): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Simpan Logbook', 'UC-12');
    }

    /**
     * Perbaiki Logbook — GET /peserta/logbook/{logbook}/edit
     * Langkah:
     *  - Hanya untuk logbook milik sendiri berstatus Perlu Perbaikan (A1).
     */
    public function edit(Request $request, Logbook $logbook): View
    {
        $this->pastikanMilikSendiri($logbook->pesertaMagang);

        return $this->halamanRancangan(
            'Perbaiki Logbook', 'UC-12', 'Peserta Magang',
            ['Hanya untuk logbook milik sendiri berstatus Perlu Perbaikan (A1).'],
            ['logbook'],
        );
    }

    /**
     * Kirim Ulang Logbook — PUT /peserta/logbook/{logbook}
     * Langkah:
     *  - Perbarui uraian, status kembali Dikirim.
     */
    public function update(Request $request, Logbook $logbook): RedirectResponse
    {
        $this->pastikanMilikSendiri($logbook->pesertaMagang);

        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Kirim Ulang Logbook', 'UC-12');
    }
}
