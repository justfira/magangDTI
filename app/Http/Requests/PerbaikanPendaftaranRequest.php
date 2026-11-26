<?php

namespace App\Http\Requests;

use App\Models\DokumenPengajuan;
use App\Models\Pengajuan;
use Illuminate\Foundation\Http\FormRequest;

class PerbaikanPendaftaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pengajuan = $this->route('pengajuan');
        if (! $pengajuan instanceof Pengajuan) {
            $pengajuan = Pengajuan::find($pengajuan);
        }

        $rules = [
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'no_hp' => ['required', 'string', 'max:30'],
            'nomor_induk_pendidikan' => ['required', 'string', 'max:40'],
            'institusi_asal' => ['required', 'string', 'max:180'],
            'jurusan_prodi' => ['required', 'string', 'max:150'],
            'dokumen' => ['nullable', 'array'],
        ];

        if ($pengajuan && $pengajuan->program) {
            foreach ($pengajuan->program->syaratDokumenProgram as $syarat) {
                $mimes = DokumenPengajuan::getMimesUntukSyarat($syarat->nama_syarat);
                $rules["dokumen.{$syarat->id_syarat}"] = [
                    'nullable',
                    'file',
                    "mimes:{$mimes}",
                    'max:' . DokumenPengajuan::MAKS_UKURAN_KB,
                ];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'dokumen.*.mimes' => 'Format berkas tidak sesuai.',
            'dokumen.*.max' => 'Ukuran berkas melebihi 2 MB.',
            'dokumen.*.file' => 'Format berkas tidak sesuai.',
            'required' => ':attribute wajib diisi.',
        ];
    }

    public function attributes(): array
    {
        $attributes = [
            'nama_lengkap' => 'Nama lengkap',
            'no_hp' => 'Nomor HP',
            'nomor_induk_pendidikan' => 'NIM / Nomor Induk Pendidikan',
            'institusi_asal' => 'Institusi asal',
            'jurusan_prodi' => 'Program studi',
            'dokumen' => 'Dokumen persyaratan',
        ];

        $pengajuan = $this->route('pengajuan');
        if (! $pengajuan instanceof Pengajuan) {
            $pengajuan = Pengajuan::find($pengajuan);
        }

        if ($pengajuan && $pengajuan->program) {
            foreach ($pengajuan->program->syaratDokumenProgram as $syarat) {
                $attributes["dokumen.{$syarat->id_syarat}"] = $syarat->nama_syarat;
            }
        }

        return $attributes;
    }
}
