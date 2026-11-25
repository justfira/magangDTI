<?php

namespace App\Http\Controllers;

use App\Models\ProgresTugas;
use App\Models\Tugas;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class TugasBerkasController extends Controller
{
    /**
     * Unduh atau lihat berkas progres tugas dengan otorisasi (Mentor bimbingan atau Peserta pemilik).
     * Jika ada query string ?unduh=1 maka dipaksa unduh, jika tidak maka ditampilkan inline (preview).
     */
    public function unduh(ProgresTugas $progres): Response
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

        if (request()->boolean('unduh')) {
            return Storage::disk('local')->download($progres->path_berkas, $namaBerkasAsli);
        }

        return Storage::disk('local')->response($progres->path_berkas, $namaBerkasAsli);
    }

    /**
     * Unduh atau lihat berkas dokumen instruksi tugas dari mentor dengan otorisasi (Mentor & Peserta).
     * Jika ada query string ?unduh=1 maka dipaksa unduh, jika tidak maka ditampilkan inline (preview).
     */
    public function unduhDokumenTugas(Tugas $tugas): Response
    {
        $peserta = $tugas->pesertaMagang;
        abort_unless($peserta, 404, 'Tugas tidak ditemukan.');

        $pengguna = auth()->user();
        $isMentor = $peserta->dibimbingOleh($pengguna?->pegawai);
        $isPeserta = $peserta->id_pengguna === $pengguna?->id_pengguna;

        abort_unless($isMentor || $isPeserta, 403, 'Akses ditolak.');

        if (! $tugas->path_dokumen || ! Storage::disk('local')->exists($tugas->path_dokumen)) {
            abort(404, 'Dokumen instruksi tidak ditemukan.');
        }

        $namaBerkasAsli = basename($tugas->path_dokumen);

        if (request()->boolean('unduh')) {
            return Storage::disk('local')->download($tugas->path_dokumen, $namaBerkasAsli);
        }

        return Storage::disk('local')->response($tugas->path_dokumen, $namaBerkasAsli);
    }
}
