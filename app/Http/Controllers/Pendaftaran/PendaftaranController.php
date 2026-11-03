<?php

namespace App\Http\Controllers\Pendaftaran;

use App\Http\Controllers\Controller;
use App\Models\ProgramMagang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mendaftar Rekrutmen DTI (UC-03)
 * Aktor  : Calon Peserta
 * Tabel  : pengguna, pengajuan, calon_peserta, dokumen_pengajuan
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class PendaftaranController extends Controller
{
    /**
     * Form Pendaftaran — GET /daftar/{program}
     * Langkah:
     *  - Tolak jika periode program sudah ditutup (A1).
     *  - Tampilkan form data akun, data diri, dan unggah berkas sesuai syarat_dokumen_program.
     */
    public function create(Request $request, ProgramMagang $program): View
    {
        return $this->halamanRancangan(
            'Form Pendaftaran', 'UC-03', 'Calon Peserta',
            ['Tolak jika periode program sudah ditutup (A1).', 'Tampilkan form data akun, data diri, dan unggah berkas sesuai syarat_dokumen_program.'],
            ['pengguna', 'pengajuan', 'calon_peserta', 'dokumen_pengajuan'],
        );
    }

    /**
     * Kirim Pendaftaran — POST /daftar/{program}
     * Langkah:
     *  - Validasi data wajib, format email, kelengkapan & format berkas (A2).
     *  - Jika belum login: buat akun pengguna (peran PESERTA, password di-hash). Jika sudah punya akun, pakai akun yang ada.
     *  - Cegah daftar dua kali pada program yang sama (UNIQUE diajukan_oleh + id_program) (A3).
     *  - Simpan pengajuan (jalur DTI, status Diajukan) + 1 calon_peserta + dokumen_pengajuan dalam satu transaksi DB.
     *  - Kirim notifikasi ke Superadmin: ada pengajuan baru.
     */
    public function store(Request $request, ProgramMagang $program): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Kirim Pendaftaran', 'UC-03');
    }
}
