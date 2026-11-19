<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\ProgramMagang;
use Illuminate\View\View;
use Throwable;

/**
 * UC-02 sisi Calon Peserta: melihat program yang dipublikasikan & sedang dibuka — SELESAI.
 * A2: tidak ada program dibuka -> "Belum ada program rekrutmen yang dibuka".
 * E2: gagal memuat -> "Informasi tidak dapat dimuat, silakan coba lagi".
 */
class InformasiProgramController extends Controller
{
    public function index(): View
    {
        try {
            $program = ProgramMagang::with('syaratDokumenProgram')
                ->where('status_publikasi', 'Dipublikasikan')
                ->whereDate('tanggal_buka', '<=', today())
                ->whereDate('tanggal_tutup', '>=', today())
                ->orderBy('tanggal_tutup')
                ->get();
        } catch (Throwable $e) {
            report($e);

            return view('publik.program', [
                'program' => collect(),
                'galat' => 'Informasi tidak dapat dimuat, silakan coba lagi.',
            ]);
        }

        return view('publik.program', compact('program'));
    }
}
