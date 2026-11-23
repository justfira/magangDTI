<?php

namespace Tests\Feature;

use App\Models\CalonPeserta;
use App\Models\DokumenPengajuan;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\ProgramMagang;
use App\Models\SyaratDokumenProgram;
use Database\Seeders\PeranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pengujian UC-03 Mendaftar Rekrutmen DTI (FR-04).
 */
class PendaftaranRekrutmenTest extends TestCase
{
    use RefreshDatabase;

    private ProgramMagang $program;
    private SyaratDokumenProgram $syaratPdf;
    private SyaratDokumenProgram $syaratFoto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PeranSeeder::class);
        Storage::fake('local');

        $adminPeran = Peran::where('kode_peran', \App\Support\Peran::SUPERADMIN)->value('id_peran');
        $admin = Pengguna::create([
            'id_peran' => $adminPeran,
            'nama_lengkap' => 'Admin Test',
            'email' => 'admin@dti.test',
            'password_hash' => Hash::make('password'),
            'status_akun' => 'Aktif',
        ]);
        $pegawai = Pegawai::create(['id_pengguna' => $admin->id_pengguna]);

        $this->program = ProgramMagang::create([
            'dibuat_oleh' => $pegawai->id_pegawai,
            'nama_program' => 'Magang DTI Batch 1',
            'tanggal_buka' => now()->subDay()->toDateString(),
            'tanggal_tutup' => now()->addDays(7)->toDateString(),
            'informasi' => 'Deskripsi program',
            'persyaratan' => 'Mahasiswa aktif',
            'status_publikasi' => 'Dipublikasikan',
        ]);

        $this->syaratPdf = SyaratDokumenProgram::create([
            'id_program' => $this->program->id_program,
            'nama_syarat' => 'Curriculum Vitae',
            'wajib' => true,
            'urutan' => 1,
        ]);

        $this->syaratFoto = SyaratDokumenProgram::create([
            'id_program' => $this->program->id_program,
            'nama_syarat' => 'Pas Foto 3x4',
            'wajib' => true,
            'urutan' => 2,
        ]);
    }

    public function test_halaman_pendaftaran_dapat_dibuka_saat_periode_buka(): void
    {
        $response = $this->get(route('pendaftaran.create', $this->program));

        $response->assertOk()
            ->assertSee('Formulir Pendaftaran Magang')
            ->assertSee('Magang DTI Batch 1')
            ->assertSee('Curriculum Vitae')
            ->assertSee('Pas Foto 3x4');
    }

    public function test_pendaftaran_ditolak_jika_periode_tutup_atau_draft(): void
    {
        // 1. Program ditutup
        $this->program->update(['status_publikasi' => 'Ditutup']);
        $this->get(route('pendaftaran.create', $this->program))
            ->assertRedirect(route('informasi-program'))
            ->assertSessionHas('gagal', 'Pendaftaran ditutup.');

        // 2. Program kedaluwarsa tanggalnya
        $this->program->update([
            'status_publikasi' => 'Dipublikasikan',
            'tanggal_buka' => now()->subDays(10)->toDateString(),
            'tanggal_tutup' => now()->subDay()->toDateString(),
        ]);
        $this->get(route('pendaftaran.create', $this->program))
            ->assertRedirect(route('informasi-program'))
            ->assertSessionHas('gagal', 'Pendaftaran ditutup.');

        // 3. POST saat ditutup juga ditolak
        $this->post(route('pendaftaran.store', $this->program), [])
            ->assertRedirect(route('informasi-program'))
            ->assertSessionHas('gagal', 'Pendaftaran ditutup.');
    }

    public function test_validasi_data_dan_dokumen_wajib(): void
    {
        $response = $this->post(route('pendaftaran.store', $this->program), []);

        $response->assertSessionHasErrors([
            'nama_lengkap',
            'email',
            'password',
            'no_hp',
            'nomor_induk_pendidikan',
            'institusi_asal',
            'jurusan_prodi',
            'dokumen',
        ]);
    }

    public function test_validasi_dokumen_kurang(): void
    {
        $response = $this->post(route('pendaftaran.store', $this->program), [
            'nama_lengkap' => 'Budi Santoso',
            'email' => 'budi@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'no_hp' => '08123456789',
            'nomor_induk_pendidikan' => '123456',
            'institusi_asal' => 'Unand',
            'jurusan_prodi' => 'SI',
            'dokumen' => [
                $this->syaratPdf->id_syarat => UploadedFile::fake()->create('cv.pdf', 500, 'application/pdf'),
                // syaratFoto tidak diunggah
            ],
        ]);

        $response->assertSessionHasErrors(["dokumen.{$this->syaratFoto->id_syarat}"]);
    }

    public function test_validasi_format_dan_ukuran_berkas_salah(): void
    {
        // Syarat PDF diunggah file teks/gambar, Syarat Foto diunggah PDF
        $response = $this->post(route('pendaftaran.store', $this->program), [
            'nama_lengkap' => 'Budi Santoso',
            'email' => 'budi@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'no_hp' => '08123456789',
            'nomor_induk_pendidikan' => '123456',
            'institusi_asal' => 'Unand',
            'jurusan_prodi' => 'SI',
            'dokumen' => [
                $this->syaratPdf->id_syarat => UploadedFile::fake()->create('cv.png', 500, 'image/png'), // Salah: harus PDF
                $this->syaratFoto->id_syarat => UploadedFile::fake()->create('foto.pdf', 500, 'application/pdf'), // Salah: harus JPG/PNG
            ],
        ]);

        $response->assertSessionHasErrors([
            "dokumen.{$this->syaratPdf->id_syarat}",
            "dokumen.{$this->syaratFoto->id_syarat}",
        ]);

        // Uji batas ukuran berkas melebihi 2 MB (2049 KB)
        $responseUkuran = $this->post(route('pendaftaran.store', $this->program), [
            'nama_lengkap' => 'Budi Santoso',
            'email' => 'budi@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'no_hp' => '08123456789',
            'nomor_induk_pendidikan' => '123456',
            'institusi_asal' => 'Unand',
            'jurusan_prodi' => 'SI',
            'dokumen' => [
                $this->syaratPdf->id_syarat => UploadedFile::fake()->create('cv.pdf', 3000, 'application/pdf'),
                $this->syaratFoto->id_syarat => UploadedFile::fake()->create('foto.jpg', 3000, 'image/jpeg'),
            ],
        ]);

        $responseUkuran->assertSessionHasErrors([
            "dokumen.{$this->syaratPdf->id_syarat}",
            "dokumen.{$this->syaratFoto->id_syarat}",
        ]);
    }

    public function test_validasi_email_duplikat(): void
    {
        $idPeranPeserta = Peran::where('kode_peran', \App\Support\Peran::PESERTA)->value('id_peran');
        Pengguna::create([
            'id_peran' => $idPeranPeserta,
            'nama_lengkap' => 'User Lama',
            'email' => 'budi@test.com',
            'password_hash' => Hash::make('password'),
            'status_akun' => 'Aktif',
        ]);

        $response = $this->post(route('pendaftaran.store', $this->program), [
            'nama_lengkap' => 'Budi Santoso',
            'email' => 'budi@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'no_hp' => '08123456789',
            'nomor_induk_pendidikan' => '123456',
            'institusi_asal' => 'Unand',
            'jurusan_prodi' => 'SI',
            'dokumen' => [
                $this->syaratPdf->id_syarat => UploadedFile::fake()->create('cv.pdf', 500, 'application/pdf'),
                $this->syaratFoto->id_syarat => UploadedFile::fake()->create('foto.jpg', 500, 'image/jpeg'),
            ],
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_pendaftaran_berhasil_dan_disimpan_dalam_transaksi(): void
    {
        $response = $this->post(route('pendaftaran.store', $this->program), [
            'nama_lengkap' => 'Rani Putri',
            'email' => 'rani@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'no_hp' => '081298765432',
            'nomor_induk_pendidikan' => '2311521001',
            'institusi_asal' => 'Universitas Andalas',
            'jurusan_prodi' => 'Sistem Informasi',
            'dokumen' => [
                $this->syaratPdf->id_syarat => UploadedFile::fake()->create('cv_rani.pdf', 500, 'application/pdf'),
                $this->syaratFoto->id_syarat => UploadedFile::fake()->create('foto_rani.jpg', 400, 'image/jpeg'),
            ],
        ]);

        $response->assertRedirect(route('peserta.pendaftaran.index'))
            ->assertSessionHas('sukses', 'Pendaftaran berhasil dikirim.');

        // User terotentikasi otomatis
        $this->assertAuthenticated();
        $user = auth()->user();
        $this->assertEquals('rani@test.com', $user->email);
        $this->assertEquals(\App\Support\Peran::PESERTA, $user->kodePeran());

        // Cek data di database
        $this->assertDatabaseHas('pengguna', ['email' => 'rani@test.com', 'nama_lengkap' => 'Rani Putri']);
        $this->assertDatabaseHas('pengajuan', [
            'id_program' => $this->program->id_program,
            'diajukan_oleh' => $user->id_pengguna,
            'status_pengajuan' => 'Diajukan',
            'jalur' => 'DTI',
        ]);
        $this->assertDatabaseHas('calon_peserta', [
            'nama_lengkap' => 'Rani Putri',
            'nomor_induk_pendidikan' => '2311521001',
            'institusi_asal' => 'Universitas Andalas',
        ]);

        // Cek format kode pengajuan
        $pengajuan = Pengajuan::where('diajukan_oleh', $user->id_pengguna)->first();
        $this->assertMatchesRegularExpression('/^DTI-\d{4}-\d{4}$/', $pengajuan->kode_pengajuan);

        // Cek penyimpanan berkas fisik di disk local privat
        $dokumenList = DokumenPengajuan::where('id_pengajuan', $pengajuan->id_pengajuan)->get();
        $this->assertCount(2, $dokumenList);
        foreach ($dokumenList as $dok) {
            Storage::disk('local')->assertExists($dok->path_berkas);
        }
    }

    public function test_peserta_dapat_melihat_status_pendaftaran(): void
    {
        $idPeranPeserta = Peran::where('kode_peran', \App\Support\Peran::PESERTA)->value('id_peran');
        $user = Pengguna::create([
            'id_peran' => $idPeranPeserta,
            'nama_lengkap' => 'Rani Putri',
            'email' => 'rani@test.com',
            'password_hash' => Hash::make('password'),
            'status_akun' => 'Aktif',
        ]);

        $pengajuan = Pengajuan::create([
            'id_program' => $this->program->id_program,
            'diajukan_oleh' => $user->id_pengguna,
            'kode_pengajuan' => 'DTI-2026-0001',
            'jalur' => 'DTI',
            'status_pengajuan' => 'Diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        CalonPeserta::create([
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'nama_lengkap' => 'Rani Putri',
            'email' => 'rani@test.com',
            'nomor_induk_pendidikan' => '2311521001',
            'institusi_asal' => 'Universitas Andalas',
            'jurusan_prodi' => 'Sistem Informasi',
        ]);

        $response = $this->actingAs($user)->get(route('peserta.pendaftaran.index'));

        $response->assertOk()
            ->assertSee('DTI-2026-0001')
            ->assertSee('Magang DTI Batch 1')
            ->assertSee('Diajukan')
            ->assertSee('2311521001');
    }

    public function test_perbaikan_hanya_dapat_diakses_jika_status_perlu_perbaikan(): void
    {
        $idPeranPeserta = Peran::where('kode_peran', \App\Support\Peran::PESERTA)->value('id_peran');
        $user = Pengguna::create([
            'id_peran' => $idPeranPeserta,
            'nama_lengkap' => 'Rani Putri',
            'email' => 'rani@test.com',
            'password_hash' => Hash::make('password'),
            'status_akun' => 'Aktif',
        ]);

        $pengajuan = Pengajuan::create([
            'id_program' => $this->program->id_program,
            'diajukan_oleh' => $user->id_pengguna,
            'kode_pengajuan' => 'DTI-2026-0001',
            'jalur' => 'DTI',
            'status_pengajuan' => 'Diajukan', // Masih Diajukan
            'tanggal_pengajuan' => now(),
        ]);

        // Ditolak jika status masih Diajukan
        $this->actingAs($user)->get(route('peserta.pendaftaran.edit', $pengajuan))
            ->assertForbidden();

        // Diizinkan jika status Perlu Perbaikan
        $pengajuan->update([
            'status_pengajuan' => 'Perlu Perbaikan',
            'catatan_perbaikan' => 'CV buram, harap perbarui.',
        ]);

        $this->actingAs($user)->get(route('peserta.pendaftaran.edit', $pengajuan))
            ->assertOk()
            ->assertSee('Perbaikan Pendaftaran Magang')
            ->assertSee('CV buram, harap perbarui.');
    }

    public function test_kirim_perbaikan_menimpa_dokumen_dan_menghapus_berkas_lama(): void
    {
        $idPeranPeserta = Peran::where('kode_peran', \App\Support\Peran::PESERTA)->value('id_peran');
        $user = Pengguna::create([
            'id_peran' => $idPeranPeserta,
            'nama_lengkap' => 'Rani Putri',
            'email' => 'rani@test.com',
            'password_hash' => Hash::make('password'),
            'status_akun' => 'Aktif',
        ]);

        $pengajuan = Pengajuan::create([
            'id_program' => $this->program->id_program,
            'diajukan_oleh' => $user->id_pengguna,
            'kode_pengajuan' => 'DTI-2026-0001',
            'jalur' => 'DTI',
            'status_pengajuan' => 'Perlu Perbaikan',
            'catatan_perbaikan' => 'CV buram.',
            'tanggal_pengajuan' => now(),
        ]);

        $calon = CalonPeserta::create([
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'nama_lengkap' => 'Rani Putri',
            'email' => 'rani@test.com',
            'no_hp' => '0812000000',
            'nomor_induk_pendidikan' => '2311521001',
            'institusi_asal' => 'Universitas Andalas',
            'jurusan_prodi' => 'Sistem Informasi',
        ]);

        // Berkas lama
        $pathLama = 'dokumen-pengajuan/' . $pengajuan->id_pengajuan . '/cv_lama.pdf';
        Storage::disk('local')->put($pathLama, 'konten lama');

        $dokumenLama = DokumenPengajuan::create([
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'id_syarat' => $this->syaratPdf->id_syarat,
            'jenis_dokumen' => $this->syaratPdf->nama_syarat,
            'nama_berkas' => 'cv_lama.pdf',
            'path_berkas' => $pathLama,
            'ukuran_bytes' => 100,
            'uploaded_at' => now()->subDay(),
            'hasil_pemeriksaan' => 'Tidak Sesuai',
            'catatan_pemeriksaan' => 'Buram',
        ]);

        // Kirim perbaikan: ubah no_hp dan unggah CV baru
        $response = $this->actingAs($user)->put(route('peserta.pendaftaran.update', $pengajuan), [
            'nama_lengkap' => 'Rani Putri Update',
            'no_hp' => '081299999999',
            'nomor_induk_pendidikan' => '2311521001',
            'institusi_asal' => 'Universitas Andalas',
            'jurusan_prodi' => 'Sistem Informasi',
            'dokumen' => [
                $this->syaratPdf->id_syarat => UploadedFile::fake()->create('cv_baru.pdf', 600, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect(route('peserta.pendaftaran.index'))
            ->assertSessionHas('sukses', 'Perbaikan berhasil dikirim.');

        // Status kembali menjadi Diajukan
        $pengajuan->refresh();
        $this->assertEquals('Diajukan', $pengajuan->status_pengajuan);

        // Data diri terupdate
        $calon->refresh();
        $this->assertEquals('Rani Putri Update', $calon->nama_lengkap);
        $this->assertEquals('081299999999', $calon->no_hp);

        // Record dokumen_pengajuan tertimpa (tetap 1 record untuk syarat ini)
        $this->assertEquals(1, DokumenPengajuan::where('id_pengajuan', $pengajuan->id_pengajuan)->where('id_syarat', $this->syaratPdf->id_syarat)->count());
        $dokumenBaru = DokumenPengajuan::find($dokumenLama->id_dokumen);
        $this->assertEquals('cv_baru.pdf', $dokumenBaru->nama_berkas);
        $this->assertNull($dokumenBaru->hasil_pemeriksaan);
        $this->assertNull($dokumenBaru->catatan_pemeriksaan);

        // Berkas lama di disk terhapus, berkas baru ada
        Storage::disk('local')->assertMissing($pathLama);
        Storage::disk('local')->assertExists($dokumenBaru->path_berkas);
    }

    public function test_otorisasi_unduh_berkas_pengajuan(): void
    {
        $idPeranPeserta = Peran::where('kode_peran', \App\Support\Peran::PESERTA)->value('id_peran');
        $pemilik = Pengguna::create([
            'id_peran' => $idPeranPeserta,
            'nama_lengkap' => 'Pemilik',
            'email' => 'pemilik@test.com',
            'password_hash' => Hash::make('password'),
            'status_akun' => 'Aktif',
        ]);

        $pesertaLain = Pengguna::create([
            'id_peran' => $idPeranPeserta,
            'nama_lengkap' => 'Orang Lain',
            'email' => 'lain@test.com',
            'password_hash' => Hash::make('password'),
            'status_akun' => 'Aktif',
        ]);

        $idPeranSuperadmin = Peran::where('kode_peran', \App\Support\Peran::SUPERADMIN)->value('id_peran');
        $superadmin = Pengguna::create([
            'id_peran' => $idPeranSuperadmin,
            'nama_lengkap' => 'Superadmin',
            'email' => 'superadmin2@test.com',
            'password_hash' => Hash::make('password'),
            'status_akun' => 'Aktif',
        ]);

        $pengajuan = Pengajuan::create([
            'id_program' => $this->program->id_program,
            'diajukan_oleh' => $pemilik->id_pengguna,
            'kode_pengajuan' => 'DTI-2026-0001',
            'jalur' => 'DTI',
            'status_pengajuan' => 'Diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        $path = 'dokumen-pengajuan/' . $pengajuan->id_pengajuan . '/rahasia.pdf';
        Storage::disk('local')->put($path, 'isi dokumen rahasia');

        $dokumen = DokumenPengajuan::create([
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'id_syarat' => $this->syaratPdf->id_syarat,
            'jenis_dokumen' => $this->syaratPdf->nama_syarat,
            'nama_berkas' => 'rahasia.pdf',
            'path_berkas' => $path,
            'ukuran_bytes' => 50,
            'uploaded_at' => now(),
        ]);

        // 1. Pemilik bisa mengunduh
        $this->actingAs($pemilik)->get(route('pengajuan.dokumen.unduh', $dokumen))
            ->assertOk();

        // 2. Superadmin bisa mengunduh
        $this->actingAs($superadmin)->get(route('pengajuan.dokumen.unduh', $dokumen))
            ->assertOk();

        // 3. Peserta lain DITOLAK 403
        $this->actingAs($pesertaLain)->get(route('pengajuan.dokumen.unduh', $dokumen))
            ->assertForbidden();

        // 4. Pengunjung tanpa login diarahkan ke login
        auth()->logout();
        $this->get(route('pengajuan.dokumen.unduh', $dokumen))
            ->assertRedirect(route('login'));
    }
}
