<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\PenyelesaianMagang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sertifikat (Peserta) (UC-15) — SUDAH DIIMPLEMENTASIKAN.
 * Aktor  : Peserta Magang
 * Tabel  : penyelesaian_magang
 *
 * Catatan: setelah penyelesaian disetujui, status_magang menjadi 'Selesai', sehingga pesertaLogin()
 * (hanya Belum Mulai/Aktif) tidak bisa dipakai. Data diambil lewat auth()->user()->pesertaMagang().
 */
class SertifikatController extends Controller
{
    private const DISK = 'local';

    /**
     * Sertifikat Magang — GET /peserta/sertifikat
     * Timeline status penyelesaian per masa magang; tombol unduh jika path_sertifikat sudah ada (A2).
     */
    public function index(Request $request): View
    {
        $pesertaMagang = $request->user()->pesertaMagang()
            ->with(['seksi', 'penyelesaianMagang' => fn ($q) => $q->latest('id_penyelesaian')])
            ->latest('id_peserta_magang')
            ->get();

        // Penyelesaian terakhir menentukan status saat ini (yang Disetujui selalu menjadi yang terakhir).
        $daftar = $pesertaMagang->map(fn ($peserta) => [
            'peserta' => $peserta,
            'penyelesaian' => $peserta->penyelesaianMagang->first(),
        ]);

        return view('peserta.sertifikat.index', compact('daftar'));
    }

    /**
     * Unduh Sertifikat — GET /peserta/sertifikat/{penyelesaian}/unduh
     * Pastikan milik sendiri; 404 bila sertifikat belum ada / file hilang (E2).
     */
    public function unduh(Request $request, PenyelesaianMagang $penyelesaian): StreamedResponse
    {
        $this->pastikanMilikSendiri($penyelesaian->pesertaMagang);

        abort_if(blank($penyelesaian->path_sertifikat), 404, 'Sertifikat belum tersedia.');
        $disk = Storage::disk(self::DISK);
        abort_unless($disk->exists($penyelesaian->path_sertifikat), 404, 'File sertifikat tidak ditemukan. Silakan hubungi Superadmin.');

        Log::channel('aktivitas')->info('sertifikat_diunduh', [
            'id_penyelesaian' => $penyelesaian->id_penyelesaian,
            'oleh' => auth()->id(),
        ]);

        $namaUnduhan = 'sertifikat-magang-'.(Str::slug((string) $penyelesaian->nomor_sertifikat) ?: $penyelesaian->id_penyelesaian).'.pdf';

        return $disk->download($penyelesaian->path_sertifikat, $namaUnduhan);
    }
}
