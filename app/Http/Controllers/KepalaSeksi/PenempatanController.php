<?php

namespace App\Http\Controllers\KepalaSeksi;

use App\Http\Controllers\Controller;
use App\Models\PesertaMagang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Penetapan Seksi & Mentor (UC-06)
 * Aktor  : Kepala Seksi DTI
 * Tabel  : peserta_magang, seksi, pegawai
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class PenempatanController extends Controller
{
    /**
     * Penetapan Seksi & Mentor — GET /kepala-seksi/penempatan
     * Langkah:
     *  - Daftar peserta_magang, filter belum/sudah ditetapkan.
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Penetapan Seksi & Mentor', 'UC-06', 'Kepala Seksi DTI',
            ['Daftar peserta_magang, filter belum/sudah ditetapkan.'],
            ['peserta_magang', 'seksi', 'pegawai'],
        );
    }

    /**
     * Tetapkan Seksi & Mentor — GET /kepala-seksi/penempatan/{peserta}/edit
     * Langkah:
     *  - Pilihan mentor = pegawai berperan MENTOR (sebaiknya dari seksi yang dipilih).
     */
    public function edit(Request $request, PesertaMagang $peserta): View
    {
        return $this->halamanRancangan(
            'Tetapkan Seksi & Mentor', 'UC-06', 'Kepala Seksi DTI',
            ['Pilihan mentor = pegawai berperan MENTOR (sebaiknya dari seksi yang dipilih).'],
            ['peserta_magang', 'seksi', 'pegawai'],
        );
    }

    /**
     * Simpan Penetapan — PUT /kepala-seksi/penempatan/{peserta}
     * Langkah:
     *  - Validasi seksi & mentor (E1); isi id_seksi, id_mentor, ditetapkan_oleh, tanggal_penempatan; notifikasi peserta & mentor.
     */
    public function update(Request $request, PesertaMagang $peserta): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Simpan Penetapan', 'UC-06');
    }
}
