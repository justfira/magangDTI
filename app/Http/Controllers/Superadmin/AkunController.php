<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\KeputusanPenerimaan;
use App\Models\Pengguna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manajemen Akun (UC-17)
 * Aktor  : Superadmin
 * Tabel  : pengguna, pegawai, peserta_magang
 *
 * Status : KERANGKA — setiap method sudah punya route, hak akses, dan daftar langkah dari A3.
 */
class AkunController extends Controller
{
    /**
     * Manajemen Akun — GET /superadmin/akun
     * Langkah:
     *  - Daftar akun beserta peran & status.
     */
    public function index(Request $request): View
    {
        return $this->halamanRancangan(
            'Manajemen Akun', 'UC-17', 'Superadmin',
            ['Daftar akun beserta peran & status.'],
            ['pengguna', 'pegawai', 'peserta_magang'],
        );
    }

    /**
     * Tambah Akun Pegawai — GET /superadmin/akun/create
     * Langkah:
     *  - Form nama, email, kata sandi, peran, seksi, NIP.
     */
    public function create(Request $request): View
    {
        return $this->halamanRancangan(
            'Tambah Akun Pegawai', 'UC-17', 'Superadmin',
            ['Form nama, email, kata sandi, peran, seksi, NIP.'],
            ['pengguna', 'pegawai', 'peserta_magang'],
        );
    }

    /**
     * Simpan Akun Pegawai — POST /superadmin/akun
     * Langkah:
     *  - Validasi, cek email unik (A2), simpan pengguna (Hash::make) + pegawai.
     */
    public function store(Request $request): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Simpan Akun Pegawai', 'UC-17');
    }

    /**
     * Ubah Akun — GET /superadmin/akun/{pengguna}/edit
     * Langkah:
     *  - (lihat use case)
     */
    public function edit(Request $request, Pengguna $pengguna): View
    {
        return $this->halamanRancangan(
            'Ubah Akun', 'UC-17', 'Superadmin',
            [],
            ['pengguna', 'pegawai', 'peserta_magang'],
        );
    }

    /**
     * Simpan Perubahan Akun — PUT /superadmin/akun/{pengguna}
     * Langkah:
     *  - (lihat use case)
     */
    public function update(Request $request, Pengguna $pengguna): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Simpan Perubahan Akun', 'UC-17');
    }

    /**
     * Nonaktifkan Akun — POST /superadmin/akun/{pengguna}/nonaktifkan
     * Langkah:
     *  - status_akun = Nonaktif (A4). Akun nonaktif tidak bisa login.
     */
    public function nonaktifkan(Request $request, Pengguna $pengguna): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Nonaktifkan Akun', 'UC-17');
    }

    /**
     * Aktifkan Kembali Akun — POST /superadmin/akun/{pengguna}/aktifkan
     * Langkah:
     *  - (lihat use case)
     */
    public function aktifkan(Request $request, Pengguna $pengguna): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Aktifkan Kembali Akun', 'UC-17');
    }

    /**
     * Aktivasi Peserta Diterima — GET /superadmin/akun/peserta-diterima
     * Langkah:
     *  - Daftar calon dengan keputusan Diterima yang belum punya peserta_magang (A3).
     */
    public function pesertaDiterima(Request $request): View
    {
        return $this->halamanRancangan(
            'Aktivasi Peserta Diterima', 'UC-17', 'Superadmin',
            ['Daftar calon dengan keputusan Diterima yang belum punya peserta_magang (A3).'],
            ['pengguna', 'pegawai', 'peserta_magang'],
        );
    }

    /**
     * Aktifkan Peserta Magang — POST /superadmin/akun/peserta-diterima/{keputusan}
     * Langkah:
     *  - Cek ulang keputusan = Diterima (A3).
     *  - Jalur INSTITUSI: buat akun pengguna (peran PESERTA) dan kirim tautan buat kata sandi. Jalur DTI: pakai akun pendaftar.
     *  - Buat peserta_magang (id_keputusan, id_pengguna, tanggal_mulai, tanggal_selesai, status Belum Mulai/Aktif).
     */
    public function aktivasiPeserta(Request $request, KeputusanPenerimaan $keputusan): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Aktifkan Peserta Magang', 'UC-17');
    }
}
