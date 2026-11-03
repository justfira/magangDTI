<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\ProgresTugas;
use App\Models\Tugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tugas Magang (Mentor) (UC-07)
 * Aktor  : Mentor DTI
 * Tabel  : tugas, progres_tugas
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class TugasController extends Controller
{
    /**
     * Tugas Peserta Bimbingan — GET /mentor/tugas
     * Langkah:
     *  - Daftar tugas untuk peserta bimbingan & progres yang menunggu review.
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Tugas Peserta Bimbingan', 'UC-07', 'Mentor DTI',
            ['Daftar tugas untuk peserta bimbingan & progres yang menunggu review.'],
            ['tugas', 'progres_tugas'],
        );
    }

    /**
     * Buat Tugas — GET /mentor/tugas/create
     * Langkah:
     *  - Pilihan peserta hanya dari peserta bimbingan.
     */
    public function create(Request $request): View
    {
        return $this->halamanRancangan(
            'Buat Tugas', 'UC-07', 'Mentor DTI',
            ['Pilihan peserta hanya dari peserta bimbingan.'],
            ['tugas', 'progres_tugas'],
        );
    }

    /**
     * Simpan Tugas — POST /mentor/tugas
     * Langkah:
     *  - Validasi judul, deskripsi, tenggat >= hari ini (A1).
     *  - Pastikan peserta adalah bimbingan mentor ini (A2) — pakai $this->pastikanBimbingan().
     *  - Simpan status Ditugaskan; notifikasi ke peserta.
     */
    public function store(Request $request): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Simpan Tugas', 'UC-07');
    }

    /**
     * Detail Tugas & Progres — GET /mentor/tugas/{tugas}
     * Langkah:
     *  - Tampilkan semua progres; progres terakhir yang belum direview bisa diperiksa.
     */
    public function show(Request $request, Tugas $tugas): View
    {
        $this->pastikanBimbingan($tugas->pesertaMagang);

        return $this->halamanRancangan(
            'Detail Tugas & Progres', 'UC-07', 'Mentor DTI',
            ['Tampilkan semua progres; progres terakhir yang belum direview bisa diperiksa.'],
            ['tugas', 'progres_tugas'],
        );
    }

    /**
     * Simpan Review Progres — POST /mentor/progres/{progres}/review
     * Langkah:
     *  - Satu progres hanya direview sekali (tolak jika hasil_review sudah terisi).
     *  - Perlu Perbaikan wajib catatan (A4) — dijaga juga oleh CHECK di database.
     *  - Update status tugas: Perlu Perbaikan / Selesai; notifikasi ke peserta.
     */
    public function review(Request $request, ProgresTugas $progres): RedirectResponse
    {
        $this->pastikanBimbingan($progres->tugas?->pesertaMagang);

        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Simpan Review Progres', 'UC-07');
    }
}
