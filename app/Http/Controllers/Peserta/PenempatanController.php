<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\PesertaMagang;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Informasi Penempatan Peserta (sisi Peserta)
 * Aktor  : Peserta Magang
 * Tabel  : peserta_magang, seksi, pegawai, pengguna
 *
 * Peserta hanya dapat melihat data penempatannya sendiri.
 * Data ditarik dari peserta_magang yang terkait akun login.
 */
class PenempatanController extends Controller
{
    /**
     * Informasi Penempatan — GET /peserta/penempatan
     *
     * Ambil semua record peserta_magang milik pengguna yang login,
     * eager-load seksi dan mentor agar tidak N+1.
     * Peserta hanya melihat data miliknya sendiri (id_pengguna = auth()->id()).
     */
    public function show(Request $request): View
    {
        $idPengguna = auth()->id();

        $daftarPeserta = PesertaMagang::where('id_pengguna', $idPengguna)
            ->with([
                'seksi:id_seksi,nama_seksi,deskripsi',
                'mentor:id_pegawai,id_pengguna,id_seksi,jabatan',
                'mentor.pengguna:id_pengguna,nama_lengkap,email',
                'keputusan.calon:id_calon,id_pengajuan,nama_lengkap,nomor_induk_pendidikan,institusi_asal,jurusan_prodi,no_hp',
                'keputusan.calon.pengajuan:id_pengajuan,kode_pengajuan,jalur,nama_institusi',
                'ditetapkanOleh:id_pegawai,id_pengguna',
                'ditetapkanOleh.pengguna:id_pengguna,nama_lengkap',
            ])
            ->orderByDesc('id_peserta_magang')
            ->get();

        return view('peserta.penempatan.show', [
            'daftarPeserta' => $daftarPeserta,
        ]);
    }
}
