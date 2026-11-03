<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use App\Models\PesertaMagang;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

abstract class Controller
{
    /** Data pegawai dari pengguna yang sedang login (null jika bukan pegawai). */
    protected function pegawaiLogin(): ?Pegawai
    {
        return auth()->user()?->pegawai;
    }

    /** Peserta magang (yang sedang berjalan) milik pengguna login. */
    protected function pesertaLogin(): ?PesertaMagang
    {
        return auth()->user()?->pesertaMagangBerjalan();
    }

    /**
     * Mentor hanya boleh memproses peserta bimbingannya sendiri.
     * Pemeriksa = peserta_magang.id_mentor (menunjuk ke tabel pegawai), jadi peserta
     * tidak mungkin tercatat sebagai pemeriksa tugas peserta lain.
     */
    protected function pastikanBimbingan(?PesertaMagang $peserta): void
    {
        abort_unless($peserta && $peserta->dibimbingOleh($this->pegawaiLogin()), 403, 'Anda tidak memiliki akses ke data peserta ini.');
    }

    /** Peserta hanya boleh mengakses datanya sendiri. */
    protected function pastikanMilikSendiri(?PesertaMagang $peserta): void
    {
        abort_unless($peserta && $peserta->id_pengguna === auth()->id(), 403, 'Anda tidak memiliki akses ke data ini.');
    }

    /** Halaman rancangan (placeholder) untuk fitur yang belum dikerjakan. */
    protected function halamanRancangan(string $judul, string $uc, string $aktor, array $todo, array $tabel): View
    {
        return view('rancangan', compact('judul', 'uc', 'aktor', 'todo', 'tabel'));
    }

    protected function belumDiimplementasikan(string $fitur, string $uc): RedirectResponse
    {
        return back()->with('info', "Fitur \"{$fitur}\" ({$uc}) belum diimplementasikan.");
    }
}
