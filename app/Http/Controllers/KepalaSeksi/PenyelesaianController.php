<?php

namespace App\Http\Controllers\KepalaSeksi;

use App\Http\Controllers\Controller;
use App\Models\PesertaMagang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Rekomendasi Penyelesaian Magang (UC-14)
 * Aktor  : Kepala Seksi DTI
 * Tabel  : penyelesaian_magang
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class PenyelesaianController extends Controller
{
    /**
     * Penyelesaian Magang — GET /kepala-seksi/penyelesaian
     * Langkah:
     *  - Daftar peserta beserta ringkasan tugas, kehadiran, logbook, evaluasi.
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Penyelesaian Magang', 'UC-14', 'Kepala Seksi DTI',
            ['Daftar peserta beserta ringkasan tugas, kehadiran, logbook, evaluasi.'],
            ['penyelesaian_magang'],
        );
    }

    /**
     * Tinjau Peserta — GET /kepala-seksi/penyelesaian/{peserta}
     * Langkah:
     *  - (lihat use case)
     */
    public function show(Request $request, PesertaMagang $peserta): View
    {
        return $this->halamanRancangan(
            'Tinjau Peserta', 'UC-14', 'Kepala Seksi DTI',
            [],
            ['penyelesaian_magang'],
        );
    }

    /**
     * Simpan Rekomendasi Penyelesaian — POST /kepala-seksi/penyelesaian/{peserta}
     * Langkah:
     *  - Layak Diselesaikan / Perlu Tindak Lanjut + catatan; buat penyelesaian_magang; notifikasi ke Direktur.
     */
    public function store(Request $request, PesertaMagang $peserta): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Simpan Rekomendasi Penyelesaian', 'UC-14');
    }
}
