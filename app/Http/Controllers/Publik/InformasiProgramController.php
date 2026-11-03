<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\ProgramMagang;
use Illuminate\View\View;

/** UC-02 sisi Calon Peserta: melihat program yang dipublikasikan & sedang dibuka — SUDAH DIIMPLEMENTASIKAN. */
class InformasiProgramController extends Controller
{
    public function index(): View
    {
        $program = ProgramMagang::with('syaratDokumenProgram')
            ->where('status_publikasi', 'Dipublikasikan')
            ->whereDate('tanggal_buka', '<=', today())
            ->whereDate('tanggal_tutup', '>=', today())
            ->orderBy('tanggal_tutup')
            ->get();

        return view('publik.program', compact('program'));   // A2: daftar kosong -> "Belum ada program rekrutmen yang dibuka"
    }
}
