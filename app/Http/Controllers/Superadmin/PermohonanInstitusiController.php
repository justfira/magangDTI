<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Permohonan Institusi (UC-04)
 * Aktor  : Superadmin
 * Tabel  : pengajuan, calon_peserta, dokumen_pengajuan
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class PermohonanInstitusiController extends Controller
{
    /**
     * Permohonan Institusi — GET /superadmin/permohonan-institusi
     * Langkah:
     *  - Daftar pengajuan jalur INSTITUSI, cari berdasarkan institusi / nomor surat.
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Permohonan Institusi', 'UC-04', 'Superadmin',
            ['Daftar pengajuan jalur INSTITUSI, cari berdasarkan institusi / nomor surat.'],
            ['pengajuan', 'calon_peserta', 'dokumen_pengajuan'],
        );
    }

    /**
     * Tambah Permohonan — GET /superadmin/permohonan-institusi/create
     * Langkah:
     *  - Form data institusi + tabel calon peserta (bisa banyak) + unggah scan surat.
     */
    public function create(Request $request): View
    {
        return $this->halamanRancangan(
            'Tambah Permohonan', 'UC-04', 'Superadmin',
            ['Form data institusi + tabel calon peserta (bisa banyak) + unggah scan surat.'],
            ['pengajuan', 'calon_peserta', 'dokumen_pengajuan'],
        );
    }

    /**
     * Simpan Permohonan — POST /superadmin/permohonan-institusi
     * Langkah:
     *  - Validasi kolom wajib & berkas surat (A1).
     *  - Simpan pengajuan (jalur INSTITUSI, dicatat_oleh = pegawai login, status Diajukan) + calon_peserta + dokumen (SURAT_PENGAJUAN) dalam satu transaksi.
     */
    public function store(Request $request): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Simpan Permohonan', 'UC-04');
    }
}
