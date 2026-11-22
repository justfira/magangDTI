<?php

namespace App\Http\Controllers;

use App\Models\DokumenPengajuan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * UC-05 — tautan "Lihat" pada checklist dokumen persyaratan.
 * Dipakai Superadmin, Kepala Seksi, dan Direktur (dibatasi middleware peran di routes/web.php).
 *
 * Berkas dicari di disk privat 'local' (storage/app/private) lebih dulu, lalu disk 'public'
 * — supaya tetap jalan apa pun disk yang dipakai UC-03 untuk menyimpan dokumen pendaftaran.
 */
class DokumenPengajuanController extends Controller
{
    public function show(DokumenPengajuan $dokumen): StreamedResponse
    {
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($dokumen->path_berkas)) {
                return Storage::disk($disk)->response($dokumen->path_berkas, $dokumen->nama_berkas);
            }
        }

        abort(404, 'Berkas tidak ditemukan.');
    }
}
