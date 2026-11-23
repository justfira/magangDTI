<?php

namespace App\Http\Controllers;

use App\Models\ProgresTugas;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TugasBerkasController extends Controller
{
    /**
     * Unduh berkas progres tugas dengan otorisasi (hanya Mentor bimbingan atau Peserta pemilik).
     */
    public function unduh(ProgresTugas $progres): StreamedResponse
    {
        $peserta = $progres->tugas?->pesertaMagang;
        abort_unless($peserta, 404, 'Tugas tidak ditemukan.');

        $pengguna = auth()->user();
        $isMentor = $peserta->dibimbingOleh($pengguna?->pegawai);
        $isPeserta = $peserta->id_pengguna === $pengguna?->id_pengguna;

        abort_unless($isMentor || $isPeserta, 403, 'Akses ditolak.');

        if (! $progres->path_berkas || ! Storage::disk('local')->exists($progres->path_berkas)) {
            abort(404, 'Berkas tidak ditemukan.');
        }

        $namaBerkasAsli = basename($progres->path_berkas);

        return Storage::disk('local')->download($progres->path_berkas, $namaBerkasAsli);
    }
}
