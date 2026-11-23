<?php

namespace App\Http\Requests\Mentor;

use App\Models\PesertaMagang;
use Illuminate\Foundation\Http\FormRequest;

class SimpanTugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()?->punyaPeran('MENTOR') ?? false);
    }

    public function rules(): array
    {
        return [
            'id_peserta_magang' => [
                'required',
                'integer',
                'exists:peserta_magang,id_peserta_magang',
                function ($attribute, $value, $fail) {
                    $peserta = PesertaMagang::find($value);
                    $mentor = auth()->user()?->pegawai;
                    if (! $peserta || ! $peserta->dibimbingOleh($mentor)) {
                        $fail('Peserta bukan merupakan peserta bimbingan Anda.');
                        return;
                    }
                    if ($peserta->status_magang !== 'Aktif') {
                        $fail('Peserta bimbingan tidak berstatus Aktif.');
                    }
                },
            ],
            'judul' => 'required|string|max:180',
            'deskripsi' => 'required|string',
            'tenggat' => 'required|date|after_or_equal:today',
            'jumlah_progres' => 'required|integer|min:1|max:100',
            'judul_progres'   => 'required|array|min:1',
            'judul_progres.*' => 'required|string|max:150',
        ];
    }

    public function messages(): array
    {
        return [
            'id_peserta_magang.required' => 'Data tugas belum lengkap (peserta belum dipilih).',
            'judul.required' => 'Data tugas belum lengkap (judul wajib diisi).',
            'deskripsi.required' => 'Data tugas belum lengkap (deskripsi wajib diisi).',
            'tenggat.required' => 'Data tugas belum lengkap (tenggat wajib diisi).',
            'tenggat.after_or_equal' => 'Tenggat tidak boleh lebih awal dari tanggal pembuatan.',
            'jumlah_progres.required' => 'Data tugas belum lengkap (jumlah progres wajib diisi).',
            'jumlah_progres.min'      => 'Jumlah progres minimal 1.',
            'judul_progres.required'  => 'Judul untuk setiap tahapan progres wajib diisi.',
            'judul_progres.*.required'=> 'Judul progres tidak boleh kosong.',
            'judul_progres.*.max'     => 'Judul progres maksimal 150 karakter.',
        ];
    }
}
