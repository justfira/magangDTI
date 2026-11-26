<?php

namespace Tests\Feature;

use App\Models\CalonPeserta;
use App\Models\KeputusanPenerimaan;
use App\Models\Notifikasi;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Pengguna;
use App\Models\Peran;
use Database\Seeders\PeranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Cek status & perbaikan permohonan institusi tanpa akun (kode permohonan + email narahubung). */
class StatusPermohonanInstitusiTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $admin;
    private Pengajuan $p;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(PeranSeeder::class);
        $this->admin = Pengguna::create([
            'id_peran' => Peran::where('kode_peran', 'SUPERADMIN')->value('id_peran'),
            'nama_lengkap' => 'Admin', 'email' => 'admin@dti.test',
            'password_hash' => Hash::make('password'), 'status_akun' => 'Aktif',
        ]);
        $pegawai = Pegawai::create(['id_pengguna' => $this->admin->id_pengguna]);

        $this->p = Pengajuan::create([
            'kode_pengajuan' => 'INS-2026-0001', 'jalur' => 'INSTITUSI',
            'nama_institusi' => 'Politeknik Negeri Padang', 'nomor_surat' => '12/PNP/2026', 'tanggal_surat' => now()->subDays(5),
            'kontak_institusi' => 'Dr. Hendra · Akademik@PNP.ac.id · 081234567890',
            'status_pengajuan' => 'Perlu Perbaikan', 'catatan_perbaikan' => 'Surat belum ditandatangani pimpinan.',
            'id_pemeriksa' => $pegawai->id_pegawai, 'waktu_pemeriksaan' => now(), 'tanggal_pengajuan' => now()->subDays(3),
        ]);
        $this->p->calonPeserta()->create(['nama_lengkap' => 'Aulia Rahma', 'nomor_induk_pendidikan' => '2101001', 'jurusan_prodi' => 'TI', 'institusi_asal' => 'Politeknik Negeri Padang']);
        Storage::disk('local')->put('permohonan-institusi/lama.pdf', '%PDF');
        $this->p->dokumenPengajuan()->create([
            'jenis_dokumen' => 'SURAT_PENGAJUAN', 'nama_berkas' => 'lama.pdf', 'path_berkas' => 'permohonan-institusi/lama.pdf',
            'hasil_pemeriksaan' => 'Tidak Sesuai', 'catatan_pemeriksaan' => 'Tanpa tanda tangan',
        ]);
    }

    private function masuk(string $kode = 'INS-2026-0001', string $email = 'akademik@pnp.ac.id')
    {
        return $this->post('/permohonan-institusi/cek', ['kode' => $kode, 'email' => $email]);
    }

    private function dataPerbaikan(array $timpa = []): array
    {
        return array_merge([
            'nama_institusi' => 'Institusi Lain (harus diabaikan)',
            'nomor_surat' => '12/PNP/2026-REV', 'tanggal_surat' => now()->subDay()->toDateString(),
            'kontak_nama' => 'Dr. Hendra', 'kontak_email' => 'akademik@pnp.ac.id', 'kontak_telepon' => '081234567890',
            'peserta' => [
                ['nama_lengkap' => 'Aulia Rahma', 'nomor_induk_pendidikan' => '2101001', 'jurusan_prodi' => 'TI'],
                ['nama_lengkap' => 'Bima Saputra', 'nomor_induk_pendidikan' => '2101002', 'jurusan_prodi' => 'TI'],
            ],
            'surat' => UploadedFile::fake()->create('surat-revisi.pdf', 200, 'application/pdf'),
        ], $timpa);
    }

    public function test_halaman_cek_bisa_dibuka_dan_kode_terisi_dari_tautan(): void
    {
        $this->get('/permohonan-institusi/cek?kode=INS-2026-0001')->assertOk()
            ->assertSee('Cek status permohonan')->assertSee('value="INS-2026-0001"', false);
    }

    public function test_kode_atau_email_salah_ditolak_dengan_pesan_yang_sama(): void
    {
        $this->masuk(email: 'orang@lain.test')->assertSessionHasErrors(['kode' => 'Kode permohonan atau email narahubung tidak cocok.']);
        $this->masuk(kode: 'INS-2026-0099')->assertSessionHasErrors(['kode' => 'Kode permohonan atau email narahubung tidak cocok.']);
        $this->get('/permohonan-institusi/status')->assertRedirect(route('publik.permohonan.cek'));
    }

    public function test_masuk_dengan_kode_dan_email_menampilkan_catatan_perbaikan(): void
    {
        // email tidak peka huruf besar/kecil, kode boleh huruf kecil
        $this->masuk('ins-2026-0001', 'AKADEMIK@pnp.ac.id')->assertRedirect(route('publik.permohonan.status'));

        $this->get('/permohonan-institusi/status')->assertOk()
            ->assertSee('Surat belum ditandatangani pimpinan.')
            ->assertSee('Tanpa tanda tangan')
            ->assertSee('Perbaiki Permohonan')
            ->assertDontSee('Admin');   // nama pegawai tidak ditampilkan ke publik
    }

    public function test_percobaan_dibatasi(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->masuk(email: "salah{$i}@x.test");
        }
        $this->masuk()->assertStatus(429);
    }

    public function test_kirim_perbaikan_mengembalikan_status_diajukan(): void
    {
        $this->masuk();
        $this->get('/permohonan-institusi/perbaikan')->assertOk()->assertSee('12/PNP/2026')->assertSee('Aulia Rahma');

        $this->put('/permohonan-institusi/perbaikan', $this->dataPerbaikan())
            ->assertRedirect(route('publik.permohonan.status'))->assertSessionHas('sukses');

        $p = $this->p->fresh();
        $this->assertSame('Diajukan', $p->status_pengajuan);
        $this->assertSame('Politeknik Negeri Padang', $p->nama_institusi);   // nama dikunci
        $this->assertSame('12/PNP/2026-REV', $p->nomor_surat);
        $this->assertSame(['Aulia Rahma', 'Bima Saputra'], $p->calonPeserta()->orderBy('id_calon')->pluck('nama_lengkap')->all());
        $this->assertSame(2, $p->dokumenPengajuan()->count());   // surat lama tetap tersimpan sebagai riwayat
        $this->assertTrue(Notifikasi::where('id_pengguna', $this->admin->id_pengguna)->where('jenis', 'PERBAIKAN_INSTITUSI')->exists());

        // setelah terkirim, form perbaikan tidak bisa dibuka lagi
        $this->get('/permohonan-institusi/perbaikan')->assertRedirect(route('publik.permohonan.status'));

        // Superadmin bisa memeriksa ulang: timeline "Diajukan ulang"
        $this->actingAs($this->admin)->get(route('superadmin.verifikasi.show', $p))->assertOk()
            ->assertSee('Diajukan ulang')->assertSee('surat-revisi.pdf');
    }

    public function test_surat_wajib_diunggah_ulang_jika_tidak_sesuai(): void
    {
        $this->masuk();
        $this->put('/permohonan-institusi/perbaikan', $this->dataPerbaikan(['surat' => null]))
            ->assertSessionHasErrors('surat');
        $this->assertSame('Perlu Perbaikan', $this->p->fresh()->status_pengajuan);
    }

    public function test_surat_opsional_jika_surat_lama_sesuai(): void
    {
        $this->p->dokumenPengajuan()->update(['hasil_pemeriksaan' => 'Sesuai', 'catatan_pemeriksaan' => null]);
        $this->masuk();
        $this->put('/permohonan-institusi/perbaikan', $this->dataPerbaikan(['surat' => null]))->assertSessionHasNoErrors();
        $this->assertSame('Diajukan', $this->p->fresh()->status_pengajuan);
        $this->assertSame(1, $this->p->dokumenPengajuan()->count());
    }

    public function test_tidak_bisa_perbaiki_saat_status_bukan_perlu_perbaikan(): void
    {
        $this->p->update(['status_pengajuan' => 'Lengkap']);
        $this->masuk();
        $this->get('/permohonan-institusi/status')->assertOk()->assertSee('Sedang Ditinjau')->assertDontSee('Perbaiki Permohonan');
        $this->put('/permohonan-institusi/perbaikan', $this->dataPerbaikan())->assertRedirect(route('publik.permohonan.status'));
        $this->assertSame('12/PNP/2026', $this->p->fresh()->nomor_surat);
    }

    public function test_hasil_keputusan_terlihat_institusi(): void
    {
        $this->p->update(['status_pengajuan' => 'Selesai Diputuskan']);
        KeputusanPenerimaan::create([
            'id_calon' => CalonPeserta::first()->id_calon, 'id_kepala_seksi' => Pegawai::first()->id_pegawai,
            'rekomendasi' => 'Tolak', 'catatan_rekomendasi' => 'Catatan internal kasi', 'waktu_rekomendasi' => now(),
            'keputusan' => 'Diterima', 'waktu_keputusan' => now(),
        ]);

        $this->masuk();
        $this->get('/permohonan-institusi/status')->assertOk()
            ->assertSee('Hasil keputusan')->assertSee('Diterima')
            ->assertDontSee('Catatan internal kasi');   // rekomendasi internal tidak bocor
    }

    public function test_keluar_menghapus_akses(): void
    {
        $this->masuk();
        $this->post('/permohonan-institusi/keluar')->assertRedirect(route('publik.permohonan.cek'));
        $this->get('/permohonan-institusi/status')->assertRedirect(route('publik.permohonan.cek'));
    }
}
