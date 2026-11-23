<?php

namespace App\Http\Requests;

use App\Models\DokumenPengajuan;
use App\Models\ProgramMagang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PendaftaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $program = $this->route('program');
        if (! $program instanceof ProgramMagang) {
            $program = ProgramMagang::find($program);
        }

        if (! $program || ! $program->isBuka()) {
            throw new HttpResponseException(
                redirect()->route('informasi-program')->with('gagal', 'Pendaftaran ditutup.')
            );
        }
    }

    public function rules(): array
    {
        $program = $this->route('program');
        if (! $program instanceof ProgramMagang) {
            $program = ProgramMagang::find($program);
        }

        $rules = [
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:pengguna,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
            'no_hp' => ['required', 'string', 'max:30'],
            'nomor_induk_pendidikan' => ['required', 'string', 'max:40'],
            'institusi_asal' => ['required', 'string', 'max:180'],
            'jurusan_prodi' => ['required', 'string', 'max:150'],
            'dokumen' => ['required', 'array'],
        ];

        if ($program) {
            foreach ($program->syaratDokumenProgram as $syarat) {
                $mimes = DokumenPengajuan::getMimesUntukSyarat($syarat->nama_syarat);
                $ruleSyarat = ['file', "mimes:{$mimes}", 'max:' . DokumenPengajuan::MAKS_UKURAN_KB];
                if ($syarat->wajib) {
                    array_unshift($ruleSyarat, 'required');
                } else {
                    array_unshift($ruleSyarat, 'nullable');
                }
                $rules["dokumen.{$syarat->id_syarat}"] = $ruleSyarat;
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email sudah terdaftar atau sudah mendaftar pada program ini.',
            'email.email' => 'Format email tidak valid.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'dokumen.*.mimes' => 'Format berkas tidak sesuai.',
            'dokumen.*.max' => 'Ukuran berkas melebihi 2 MB.',
            'dokumen.*.file' => 'Format berkas tidak sesuai.',
            'dokumen.*.required' => 'Dokumen persyaratan belum lengkap.',
            'dokumen.required' => 'Data atau dokumen belum lengkap.',
            'required' => ':attribute wajib diisi.',
        ];
    }

    public function attributes(): array
    {
        $attributes = [
            'nama_lengkap' => 'Nama lengkap',
            'email' => 'Email',
            'password' => 'Kata sandi',
            'password_confirmation' => 'Konfirmasi kata sandi',
            'no_hp' => 'Nomor HP',
            'nomor_induk_pendidikan' => 'NIM / Nomor Induk Pendidikan',
            'institusi_asal' => 'Institusi asal',
            'jurusan_prodi' => 'Program studi',
            'dokumen' => 'Dokumen persyaratan',
        ];

        $program = $this->route('program');
        if (! $program instanceof ProgramMagang) {
            $program = ProgramMagang::find($program);
        }

        if ($program) {
            foreach ($program->syaratDokumenProgram as $syarat) {
                $attributes["dokumen.{$syarat->id_syarat}"] = $syarat->nama_syarat;
            }
        }

        return $attributes;
    }
}
