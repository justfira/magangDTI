<?php

namespace App\Services;

use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Pengguna;
use App\Support\Notifikasi;
use App\Support\Peran;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

/**
 * UC-04 Mencatat Permohonan Magang Institusi — logika bersama untuk dua pintu masuk:
 *   - Superadmin mencatat surat fisik  (Superadmin\PermohonanInstitusiController)
 *   - Institusi mengisi form online    (Publik\PermohonanInstitusiController)
 *
 * Tabel: pengajuan (jalur INSTITUSI) + calon_peserta (bisa banyak) + dokumen_pengajuan (SURAT_PENGAJUAN),
 * disimpan dalam SATU transaksi (A3 langkah 8). Gagal di tengah jalan -> semua dibatalkan & berkas dihapus (E1).
 */
class PermohonanInstitusiService
{
    public const JENIS_DOKUMEN = 'SURAT_PENGAJUAN';
    public const DISK = 'local';                     // storage/app/private — tidak bisa diakses publik
    public const FOLDER = 'permohonan-institusi';
    public const FORMAT_SURAT = ['pdf', 'jpg', 'jpeg', 'png'];
    public const MAKS_KB = 2048;                     // 2 MB
    public const MAKS_PESERTA = 20;

    public const PESAN_SUKSES = 'Permohonan berhasil dicatat.';
    public const PESAN_TIDAK_VALID = 'Data belum lengkap atau berkas tidak sesuai.';   // A1
    public const PESAN_GAGAL_SIMPAN = 'Gagal menyimpan permohonan, silakan coba lagi.'; // E1
    public const PESAN_GAGAL_UNGGAH = 'Berkas gagal diunggah, silakan coba lagi.';      // E2

    /** Kolom per baris peserta pada tabel form. */
    public const KOLOM_PESERTA = ['nama_lengkap', 'nomor_induk_pendidikan', 'jurusan_prodi', 'email', 'no_hp'];

    /**
     * Validasi A1 dengan pesan error per kolom. Baris peserta yang dibiarkan kosong seluruhnya diabaikan.
     *
     * @return \Illuminate\Validation\Validator
     */
    public function validator(Request $request, array $aturanTambahan = [], array $pesanTambahan = [])
    {
        $request->merge(['peserta' => $this->barisTerisi($request->input('peserta', []))]);

        $telepon = 'regex:/^[0-9+()\-\s]{8,20}$/';

        return Validator::make($request->all(), array_merge([
            'nama_institusi' => ['required', 'string', 'max:180'],
            'nomor_surat' => ['required', 'string', 'max:100'],
            'tanggal_surat' => ['required', 'date', 'before_or_equal:today'],
            'kontak_nama' => ['required', 'string', 'max:60'],
            'kontak_email' => ['required', 'email', 'max:60'],
            'kontak_telepon' => ['required', $telepon],
            'peserta' => ['required', 'array', 'min:1', 'max:'.self::MAKS_PESERTA],
            'peserta.*.nama_lengkap' => ['required', 'string', 'max:150'],
            'peserta.*.nomor_induk_pendidikan' => ['required', 'string', 'max:40', 'distinct:ignore_case'],
            'peserta.*.jurusan_prodi' => ['required', 'string', 'max:150'],
            'peserta.*.email' => ['nullable', 'email', 'max:191'],
            'peserta.*.no_hp' => ['nullable', $telepon],
            'surat' => ['required', 'file', 'mimes:'.implode(',', self::FORMAT_SURAT), 'max:'.self::MAKS_KB],
        ], $aturanTambahan), array_merge([
            'required' => ':attribute wajib diisi.',
            'email' => ':attribute harus berupa alamat email yang valid.',
            'max' => ':attribute maksimal :max karakter.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'regex' => ':attribute hanya boleh berisi angka (8–20 digit), boleh diawali +62.',
            'tanggal_surat.before_or_equal' => 'Tanggal surat tidak boleh melebihi hari ini.',
            'peserta.required' => 'Tambahkan minimal satu data peserta.',
            'peserta.min' => 'Tambahkan minimal satu data peserta.',
            'peserta.max' => 'Satu permohonan maksimal memuat '.self::MAKS_PESERTA.' peserta.',
            'peserta.*.nomor_induk_pendidikan.distinct' => 'NIM/NIS pada baris :position sama dengan baris lain.',
            'surat.required' => 'Scan surat pengajuan wajib diunggah.',
            'surat.file' => 'Scan surat pengajuan wajib diunggah.',
            'surat.uploaded' => self::PESAN_GAGAL_UNGGAH,
            'surat.mimes' => 'Format surat harus '.strtoupper(implode(', ', ['pdf', 'jpg', 'png'])).'.',
            'surat.max' => 'Ukuran surat maksimal '.(self::MAKS_KB / 1024).' MB.',
        ], $pesanTambahan), [
            'nama_institusi' => 'Nama institusi',
            'nomor_surat' => 'Nomor surat',
            'tanggal_surat' => 'Tanggal surat',
            'kontak_nama' => 'Nama narahubung',
            'kontak_email' => 'Email narahubung',
            'kontak_telepon' => 'No. HP/WA narahubung',
            'peserta.*.nama_lengkap' => 'Nama peserta baris :position',
            'peserta.*.nomor_induk_pendidikan' => 'NIM/NIS baris :position',
            'peserta.*.jurusan_prodi' => 'Jurusan/prodi baris :position',
            'peserta.*.email' => 'Email peserta baris :position',
            'peserta.*.no_hp' => 'No. HP peserta baris :position',
            'surat' => 'Scan surat pengajuan',
        ]);
    }

    /**
     * Simpan permohonan dengan status awal "Diajukan".
     *
     * @param  Pegawai|null  $pencatat  Superadmin yang mencatat; null = diisi institusi lewat form online.
     *
     * @throws GagalUnggahSurat jika berkas tidak bisa disimpan (E2)
     * @throws Throwable jika transaksi database gagal (E1)
     */
    public function simpan(array $data, UploadedFile $surat, ?Pegawai $pencatat): Pengajuan
    {
        $path = $this->unggah($surat);

        try {
            $pengajuan = DB::transaction(function () use ($data, $surat, $path, $pencatat) {
                $pengajuan = Pengajuan::create([
                    'kode_pengajuan' => $this->kodeBaru(),
                    'jalur' => 'INSTITUSI',
                    'dicatat_oleh' => $pencatat?->id_pegawai,
                    'nama_institusi' => trim($data['nama_institusi']),
                    'nomor_surat' => trim($data['nomor_surat']),
                    'tanggal_surat' => $data['tanggal_surat'],
                    'kontak_institusi' => $this->gabungKontak($data),
                    'status_pengajuan' => 'Diajukan',
                    'tanggal_pengajuan' => now(),
                ]);

                foreach ($data['peserta'] as $baris) {
                    $pengajuan->calonPeserta()->create([
                        'nama_lengkap' => trim($baris['nama_lengkap']),
                        'nomor_induk_pendidikan' => trim($baris['nomor_induk_pendidikan']),
                        'jurusan_prodi' => trim($baris['jurusan_prodi']),
                        'email' => $this->kosongJadiNull($baris['email'] ?? null),
                        'no_hp' => $this->kosongJadiNull($baris['no_hp'] ?? null),
                        'institusi_asal' => trim($data['nama_institusi']),
                    ]);
                }

                $pengajuan->dokumenPengajuan()->create([
                    'jenis_dokumen' => self::JENIS_DOKUMEN,
                    'nama_berkas' => mb_substr($surat->getClientOriginalName(), 0, 255),
                    'path_berkas' => $path,
                    'ukuran_bytes' => $surat->getSize(),
                    'uploaded_at' => now(),
                ]);

                if (! $pencatat) {
                    $this->beritahuSuperadmin($pengajuan, count($data['peserta']));
                }

                return $pengajuan;
            });
        } catch (Throwable $e) {
            Storage::disk(self::DISK)->delete($path);   // jangan tinggalkan berkas yatim
            throw $e;
        }

        return $pengajuan;
    }

    /**
     * Cek status (publik): cari permohonan institusi lewat kode + email narahubung.
     * Kode berurutan sehingga mudah ditebak — karena itu email wajib cocok juga.
     */
    public function cariUntukInstitusi(string $kode, string $email): ?Pengajuan
    {
        $pengajuan = Pengajuan::where('jalur', 'INSTITUSI')
            ->where('kode_pengajuan', strtoupper(trim($kode)))
            ->first();

        if (! $pengajuan || ! $this->emailCocok($pengajuan, $email)) {
            return null;
        }

        return $pengajuan;
    }

    /** Email dibandingkan dengan bagian email di kontak_institusi ("Nama · email · telp"), tanpa beda huruf besar/kecil. */
    public function emailCocok(Pengajuan $pengajuan, string $email): bool
    {
        $email = mb_strtolower(trim($email));
        $kontak = $this->pecahKontak($pengajuan->kontak_institusi);

        return $email !== '' && $kontak['email'] !== '' && hash_equals(mb_strtolower($kontak['email']), $email);
    }

    /** Kebalikan gabungKontak(): pisahkan nama, email, dan telepon. */
    public function pecahKontak(?string $kontak): array
    {
        $hasil = ['nama' => '', 'email' => '', 'telepon' => ''];
        foreach (array_map('trim', explode('·', (string) $kontak)) as $bagian) {
            if ($bagian === '') {
                continue;
            }
            if ($hasil['email'] === '' && filter_var($bagian, FILTER_VALIDATE_EMAIL)) {
                $hasil['email'] = $bagian;
            } elseif ($hasil['telepon'] === '' && preg_match('/^[0-9+()\-\s]{8,20}$/', $bagian)) {
                $hasil['telepon'] = $bagian;
            } elseif ($hasil['nama'] === '') {
                $hasil['nama'] = $bagian;
            }
        }

        return $hasil;
    }

    /**
     * Institusi mengirim perbaikan (status Perlu Perbaikan -> Diajukan lagi).
     * Nama institusi dikunci. Peserta diganti dengan isi tabel terbaru (aman: rekomendasi baru ada setelah status Lengkap).
     * Surat baru (opsional) ditambahkan sebagai berkas terbaru; berkas lama tetap tersimpan sebagai riwayat.
     *
     * @throws GagalUnggahSurat
     * @throws Throwable
     */
    public function perbaiki(Pengajuan $pengajuan, array $data, ?UploadedFile $surat): Pengajuan
    {
        $path = $surat ? $this->unggah($surat) : null;

        try {
            DB::transaction(function () use ($pengajuan, $data, $surat, $path) {
                $terkunci = Pengajuan::whereKey($pengajuan->id_pengajuan)->lockForUpdate()->first();
                if ($terkunci->status_pengajuan !== 'Perlu Perbaikan' || $terkunci->calonPeserta()->whereHas('keputusanPenerimaan')->exists()) {
                    throw new RuntimeException('Permohonan tidak dalam status Perlu Perbaikan.');
                }

                $terkunci->update([
                    'nomor_surat' => trim($data['nomor_surat']),
                    'tanggal_surat' => $data['tanggal_surat'],
                    'kontak_institusi' => $this->gabungKontak($data),
                    'status_pengajuan' => 'Diajukan',
                    'updated_at' => now(),
                ]);

                $terkunci->calonPeserta()->delete();
                foreach ($data['peserta'] as $baris) {
                    $terkunci->calonPeserta()->create([
                        'nama_lengkap' => trim($baris['nama_lengkap']),
                        'nomor_induk_pendidikan' => trim($baris['nomor_induk_pendidikan']),
                        'jurusan_prodi' => trim($baris['jurusan_prodi']),
                        'email' => $this->kosongJadiNull($baris['email'] ?? null),
                        'no_hp' => $this->kosongJadiNull($baris['no_hp'] ?? null),
                        'institusi_asal' => $terkunci->nama_institusi,
                    ]);
                }

                if ($surat && $path) {
                    $terkunci->dokumenPengajuan()->create([
                        'jenis_dokumen' => self::JENIS_DOKUMEN,
                        'nama_berkas' => mb_substr($surat->getClientOriginalName(), 0, 255),
                        'path_berkas' => $path,
                        'ukuran_bytes' => $surat->getSize(),
                        'uploaded_at' => now(),
                    ]);
                }

                AlurPenerimaanService::beritahuPeran(Peran::SUPERADMIN, 'PERBAIKAN_INSTITUSI',
                    "Perbaikan permohonan {$terkunci->kode_pengajuan} dari {$terkunci->nama_institusi} sudah masuk dan menunggu pemeriksaan ulang.",
                    'pengajuan', $terkunci->id_pengajuan);
            });
        } catch (Throwable $e) {
            if ($path) {
                Storage::disk(self::DISK)->delete($path);
            }
            throw $e;
        }

        return $pengajuan->fresh();
    }

    /** Surat wajib diunggah ulang bila surat terakhir ditandai Tidak Sesuai oleh Superadmin. */
    public function suratWajibDiganti(Pengajuan $pengajuan): bool
    {
        return $pengajuan->dokumenPengajuan()
            ->where('jenis_dokumen', self::JENIS_DOKUMEN)
            ->latest('id_dokumen')
            ->value('hasil_pemeriksaan') === 'Tidak Sesuai';
    }

    /** Kontak institusi disimpan dalam satu kolom (kontak_institusi, maks 150) sesuai ERD. */
    public function gabungKontak(array $data): string
    {
        return mb_substr(implode(' · ', array_filter([
            trim($data['kontak_nama'] ?? ''),
            trim($data['kontak_email'] ?? ''),
            trim($data['kontak_telepon'] ?? ''),
        ])), 0, 150);
    }

    /** E2: unggah gagal (koneksi terputus / disk penuh) -> GagalUnggahSurat. */
    private function unggah(UploadedFile $surat): string
    {
        if (! $surat->isValid()) {
            throw new GagalUnggahSurat(self::PESAN_GAGAL_UNGGAH);
        }
        try {
            $path = $surat->store(self::FOLDER.'/'.now()->format('Y/m'), self::DISK);
        } catch (Throwable $e) {
            report($e);
            $path = false;
        }
        if (! $path) {
            throw new GagalUnggahSurat(self::PESAN_GAGAL_UNGGAH);
        }

        return $path;
    }

    /** Kode unik: INS-2026-0001, INS-2026-0002, ... (berurutan per tahun). */
    private function kodeBaru(): string
    {
        $awalan = 'INS-'.now()->year.'-';
        $terakhir = Pengajuan::where('kode_pengajuan', 'like', $awalan.'%')
            ->lockForUpdate()
            ->orderByDesc('kode_pengajuan')
            ->value('kode_pengajuan');
        $nomor = $terakhir ? ((int) substr($terakhir, strlen($awalan))) + 1 : 1;

        return $awalan.str_pad((string) $nomor, 4, '0', STR_PAD_LEFT);
    }

    /** Notifikasi lonceng ke semua Superadmin aktif bahwa ada permohonan online baru. */
    private function beritahuSuperadmin(Pengajuan $pengajuan, int $jumlahPeserta): void
    {
        Pengguna::where('status_akun', 'Aktif')
            ->whereHas('peran', fn ($q) => $q->where('kode_peran', Peran::SUPERADMIN))
            ->pluck('id_pengguna')
            ->each(fn ($id) => Notifikasi::kirim(
                $id,
                'PERMOHONAN_INSTITUSI',
                "Permohonan magang baru dari {$pengajuan->nama_institusi} ({$jumlahPeserta} peserta) masuk lewat form online.",
                'pengajuan',
                $pengajuan->id_pengajuan,
            ));
    }

    /** Buang baris peserta yang seluruh kolomnya kosong (baris tambahan yang tidak diisi). */
    private function barisTerisi(mixed $peserta): array
    {
        if (! is_array($peserta)) {
            return [];
        }

        return array_values(array_filter($peserta, function ($baris) {
            if (! is_array($baris)) {
                return false;
            }
            foreach (self::KOLOM_PESERTA as $kolom) {
                if (trim((string) ($baris[$kolom] ?? '')) !== '') {
                    return true;
                }
            }

            return false;
        }));
    }

    private function kosongJadiNull(?string $nilai): ?string
    {
        $nilai = trim((string) $nilai);

        return $nilai === '' ? null : $nilai;
    }
}

