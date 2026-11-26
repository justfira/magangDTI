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

    protected function prepareForValidation(): void
    {
        if ($this->has('id_peserta_magang') && ! is_array($this->id_peserta_magang)) {
            $this->merge([
                'id_peserta_magang' => [$this->id_peserta_magang],
            ]);
        }

        if (! $this->has('judul_progres') && $this->filled('jumlah_progres')) {
            $jumlah = (int) $this->jumlah_progres;
            $judulProgres = [];
            for ($i = 1; $i <= $jumlah; $i++) {
                $judulProgres[] = "Tahap {$i}";
            }
            $this->merge([
                'judul_progres' => $judulProgres,
            ]);
        } elseif ($this->has('judul_progres') && is_array($this->judul_progres) && ! $this->filled('jumlah_progres')) {
            $this->merge([
                'jumlah_progres' => count($this->judul_progres),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'id_peserta_magang' => 'required|array|min:1',
            'id_peserta_magang.*' => 'required|integer|exists:peserta_magang,id_peserta_magang',
            'judul' => 'required|string|max:180',
            'deskripsi' => 'required|string',
            'tenggat' => 'required|date|after_or_equal:now',
            'jumlah_progres'      => 'required|integer|min:1|max:100',
            'judul_progres'       => 'required|array|min:1',
            'judul_progres.*'     => 'required|string|max:150',
            'dokumen'             => 'nullable|file|mimes:pdf,zip,rar,doc,docx,png,jpg,jpeg|max:10240',
            'nama_kustom_dokumen' => 'nullable|string|max:100',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ids = $this->input('id_peserta_magang', []);
            if (! is_array($ids)) {
                return;
            }
            $mentor = auth()->user()?->pegawai;
            foreach ($ids as $id) {
                $peserta = PesertaMagang::find($id);
                if (! $peserta || ! $peserta->dibimbingOleh($mentor)) {
                    $validator->errors()->add('id_peserta_magang', 'Peserta bukan merupakan peserta bimbingan Anda.');
                    break;
                }
                if ($peserta->status_magang !== 'Aktif') {
                    $validator->errors()->add('id_peserta_magang', 'Peserta bimbingan tidak berstatus Aktif.');
                    break;
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'id_peserta_magang.required' => 'Data tugas belum lengkap (peserta belum dipilih).',
            'judul.required' => 'Data tugas belum lengkap (judul wajib diisi).',
            'deskripsi.required' => 'Data tugas belum lengkap (deskripsi wajib diisi).',
            'tenggat.required' => 'Data tugas belum lengkap (tenggat wajib diisi).',
            'tenggat.after_or_equal' => 'Tenggat waktu tidak boleh lebih awal dari waktu saat ini.',
            'jumlah_progres.required' => 'Data tugas belum lengkap (jumlah progres wajib diisi).',
            'jumlah_progres.min'      => 'Jumlah progres minimal 1.',
            'judul_progres.required'  => 'Judul untuk setiap tahapan progres wajib diisi.',
            'judul_progres.*.required'=> 'Judul progres tidak boleh kosong.',
            'judul_progres.*.max'     => 'Judul progres maksimal 150 karakter.',
            'dokumen.file'            => 'Berkas instruksi harus berupa file yang valid.',
            'dokumen.mimes'           => 'Format berkas instruksi tidak didukung. Format yang diizinkan: pdf, zip, rar, doc, docx, png, jpg, jpeg.',
            'dokumen.max'             => 'Ukuran berkas instruksi maksimal 10 MB.',
        ];
    }
}
