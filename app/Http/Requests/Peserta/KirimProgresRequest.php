<?php

namespace App\Http\Requests\Peserta;

use App\Models\ProgresTugas;
use Illuminate\Foundation\Http\FormRequest;

class KirimProgresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()?->punyaPeran('PESERTA') ?? false);
    }

    public function rules(): array
    {
        $mimes = implode(',', ProgresTugas::FORMAT_BERKAS);
        $maxKb = ProgresTugas::MAKS_UKURAN_BERKAS_KB;

        return [
            'catatan_progres'    => 'required|string',
            'tautan_hasil'       => 'nullable|url|max:500',
            'berkas'             => "required|file|mimes:{$mimes}|max:{$maxKb}",
            'nama_kustom_berkas' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'catatan_progres.required' => 'Progres atau hasil tugas belum diisi (catatan progres wajib diisi).',
            'tautan_hasil.url' => 'Tautan hasil harus berupa URL yang valid (diawali http:// atau https://).',
            'berkas.required' => 'Progres atau hasil tugas belum diisi (berkas hasil tugas wajib diunggah).',
            'berkas.file' => 'Berkas harus berupa file yang valid.',
            'berkas.mimes' => 'Format berkas tidak didukung. Format yang diizinkan: pdf, zip, rar, doc, docx, png, jpg, jpeg.',
            'berkas.max' => 'Ukuran berkas maksimal 10 MB.',
        ];
    }
}
