<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\DokumenPengajuan;
use App\Support\Peran;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DokumenPengajuanController extends Controller
{
    /**
     * Unduh berkas dokumen pengajuan dengan otorisasi:
     * Pemilik pengajuan (calon/peserta) atau pemeriksa (Superadmin, Kepala Seksi, Direktur).
     */
    public function unduh(DokumenPengajuan $dokumen): StreamedResponse
    {
        $pengguna = auth()->user();
        $pengajuan = $dokumen->pengajuan;
        abort_unless($pengajuan, 404, 'Pengajuan tidak ditemukan.');

        $isPemilik = ($pengajuan->diajukan_oleh === $pengguna?->id_pengguna);
        $isPemeriksa = $pengguna?->punyaPeran(
            Peran::SUPERADMIN,
            Peran::KEPALA_SEKSI,
            Peran::DIREKTUR
        );

        abort_unless($isPemilik || $isPemeriksa, 403, 'Anda tidak memiliki akses ke berkas ini.');

        if (! $dokumen->path_berkas || ! Storage::disk('local')->exists($dokumen->path_berkas)) {
            abort(404, 'Berkas fisik tidak ditemukan.');
        }

        $namaBerkas = $dokumen->nama_berkas ?: basename($dokumen->path_berkas);

        return Storage::disk('local')->download($dokumen->path_berkas, $namaBerkas);
    }
}
