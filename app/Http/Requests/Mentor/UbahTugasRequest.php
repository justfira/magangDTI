<?php

namespace App\Http\Requests\Mentor;

use Illuminate\Foundation\Http\FormRequest;

class UbahTugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()?->punyaPeran('MENTOR') ?? false);
    }

    public function rules(): array
    {
        return [
            'judul' => 'required|string|max:180',
            'deskripsi' => 'required|string',
            'tenggat' => 'required|date',
            'judul_progres' => 'required|array|min:1',
            'judul_progres.*' => 'required|string|max:150',
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required' => 'Judul tugas wajib diisi.',
            'judul.max' => 'Judul tugas maksimal 180 karakter.',
            'deskripsi.required' => 'Deskripsi tugas wajib diisi.',
            'tenggat.required' => 'Tenggat waktu tugas wajib diisi.',
            'tenggat.date' => 'Format tenggat waktu tidak valid.',
            'judul_progres.required' => 'Minimal harus ada 1 tahapan progres.',
            'judul_progres.min' => 'Minimal harus ada 1 tahapan progres.',
            'judul_progres.*.required' => 'Judul tahapan progres tidak boleh kosong.',
            'judul_progres.*.max' => 'Judul tahapan progres maksimal 150 karakter.',
        ];
    }
}
