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
        ];
    }
}
