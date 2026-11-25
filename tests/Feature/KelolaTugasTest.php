<?php

namespace Tests\Feature;

use App\Models\CalonPeserta;
use App\Models\KeputusanPenerimaan;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\PesertaMagang;
use App\Models\ProgramMagang;
use App\Models\ProgresTugas;
use App\Models\Tugas;
use Database\Seeders\PeranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pengujian UC-07 Mengelola Tugas Magang (FR-10, FR-11, FR-12)
 */
class KelolaTugasTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $mentorUser;
    private Pegawai $mentorPegawai;
    private Pengguna $pesertaUser;
    private PesertaMagang $pesertaMagang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PeranSeeder::class);
        Storage::fake('local');

        // Mentor
        $this->mentorUser = $this->buatAkun('MENTOR', 'mentor@dti.test', 'Budi Santoso');
        $this->mentorPegawai = Pegawai::create([
            'id_pengguna'  => $this->mentorUser->id_pengguna,
            'nama_lengkap' => 'Budi Santoso',
        ]);

        // Peserta & Pengajuan
        $this->pesertaUser = $this->buatAkun('PESERTA', 'peserta@dti.test', 'Rani Putri');

        $program = ProgramMagang::create([
            'nama_program' => 'Magang DTI Test',
            'dibuat_oleh' => $this->mentorPegawai->id_pegawai,
            'tanggal_buka' => now()->subDays(30),
            'tanggal_tutup'=> now()->addDays(30),
            'status_publikasi' => 'Dipublikasikan',
        ]);

        $pengajuan = Pengajuan::create([
            'kode_pengajuan' => 'DTI-TEST-0001',
            'jalur' => 'DTI',
            'id_program' => $program->id_program,
            'diajukan_oleh' => $this->pesertaUser->id_pengguna,
            'status_pengajuan' => 'Selesai Diputuskan',
            'tanggal_pengajuan' => now()->subDays(15),
        ]);

        $calonPeserta = CalonPeserta::create([
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'nama_lengkap' => 'Rani Putri',
            'email' => 'peserta@dti.test',
            'nomor_induk_pendidikan' => '2311521001',
            'institusi_asal' => 'Universitas Andalas',
            'jurusan_prodi' => 'Sistem Informasi',
        ]);

        $keputusan = KeputusanPenerimaan::create([
            'id_calon' => $calonPeserta->id_calon,
            'id_kepala_seksi' => $this->mentorPegawai->id_pegawai,
            'id_direktur' => $this->mentorPegawai->id_pegawai,
            'rekomendasi' => 'Terima',
            'keputusan' => 'Diterima',
            'waktu_keputusan' => now()->subDays(10),
        ]);

        $this->pesertaMagang = PesertaMagang::create([
            'id_keputusan'   => $keputusan->id_keputusan,
            'id_pengguna'    => $this->pesertaUser->id_pengguna,
            'id_mentor'      => $this->mentorPegawai->id_pegawai,
            'tanggal_mulai'  => now()->subDays(10)->toDateString(),
            'tanggal_selesai'=> now()->addDays(60)->toDateString(),
            'status_magang'  => 'Aktif',
        ]);
    }

    private function buatAkun(string $kodePeran, string $email, string $nama): Pengguna
    {
        return Pengguna::create([
            'id_peran'      => Peran::where('kode_peran', $kodePeran)->value('id_peran'),
            'nama_lengkap'  => $nama,
            'email'         => $email,
            'password_hash' => Hash::make('password'),
            'status_akun'   => 'Aktif',
        ]);
    }

    public function test_mentor_dapat_melihat_halaman_index_dan_create_tugas(): void
    {
        $response = $this->actingAs($this->mentorUser)->get(route('mentor.tugas.index'));
        $response->assertOk();
        $response->assertSee('Tugas Peserta Bimbingan');

        $responseCreate = $this->actingAs($this->mentorUser)->get(route('mentor.tugas.create'));
        $responseCreate->assertOk();
        $responseCreate->assertSee('Buat Tugas Baru');
        $responseCreate->assertSee($this->pesertaUser->nama_lengkap);
    }

    public function test_mentor_dapat_membuat_tugas_dengan_jumlah_progres(): void
    {
        $response = $this->actingAs($this->mentorUser)->post(route('mentor.tugas.store'), [
            'id_peserta_magang' => $this->pesertaMagang->id_peserta_magang,
            'judul'             => 'Implementasi REST API Magang',
            'deskripsi'         => 'Buat endpoint CRUD dan dokumentasi API.',
            'tenggat'           => now()->addDays(7)->toDateString(),
            'jumlah_progres'    => 2,
            'judul_progres'     => ['Tahap 1: Desain Endpoint', 'Tahap 2: Implementasi & Dokumentasi'],
        ]);

        $response->assertSessionHas('sukses');
        $this->assertDatabaseHas('tugas', [
            'id_peserta_magang' => $this->pesertaMagang->id_peserta_magang,
            'judul'             => 'Implementasi REST API Magang',
            'jumlah_progres'    => 2,
            'status'            => 'Ditugaskan',
        ]);
    }

    public function test_mentor_gagal_membuat_tugas_jika_data_tidak_lengkap_atau_tenggat_lampau(): void
    {
        $response = $this->actingAs($this->mentorUser)->post(route('mentor.tugas.store'), [
            'id_peserta_magang' => $this->pesertaMagang->id_peserta_magang,
            'judul'             => '',
            'deskripsi'         => '',
            'tenggat'           => now()->subDays(1)->toDateString(),
            'jumlah_progres'    => 0,
        ]);

        $response->assertSessionHasErrors(['judul', 'deskripsi', 'tenggat', 'jumlah_progres']);
        $this->assertEquals(0, Tugas::count());
    }

    public function test_mentor_tidak_bisa_membuat_tugas_untuk_peserta_bukan_bimbingannya(): void
    {
        $mentorLain = $this->buatAkun('MENTOR', 'mentorlain@dti.test', 'Mentor Lain');
        Pegawai::create([
            'id_pengguna'  => $mentorLain->id_pengguna,
            'nama_lengkap' => 'Mentor Lain',
        ]);

        $response = $this->actingAs($mentorLain)->post(route('mentor.tugas.store'), [
            'id_peserta_magang' => $this->pesertaMagang->id_peserta_magang,
            'judul'             => 'Tugas Tidak Sah',
            'deskripsi'         => 'Deskripsi tugas.',
            'tenggat'           => now()->addDays(5)->toDateString(),
            'jumlah_progres'    => 1,
        ]);

        $response->assertSessionHasErrors('id_peserta_magang');
        $this->assertEquals(0, Tugas::count());
    }

    public function test_peserta_dapat_melihat_daftar_dan_detail_tugas(): void
    {
        $tugas = Tugas::create([
            'id_peserta_magang' => $this->pesertaMagang->id_peserta_magang,
            'judul'             => 'Tugas Analisis Kebutuhan',
            'deskripsi'         => 'Rancang dokumen analisis sistem.',
            'tenggat'           => now()->addDays(5)->toDateString(),
            'jumlah_progres'    => 2,
            'status'            => 'Ditugaskan',
        ]);

        $response = $this->actingAs($this->pesertaUser)->get(route('peserta.tugas.index'));
        $response->assertOk();
        $response->assertSee('Tugas Analisis Kebutuhan');

        $responseDetail = $this->actingAs($this->pesertaUser)->get(route('peserta.tugas.show', $tugas));
        $responseDetail->assertOk();
        $responseDetail->assertSee('Progres ke-0 dari 2');
        $responseDetail->assertSee('Kirim Progres ke-1 dari 2');
    }

    public function test_peserta_dapat_mengirim_progres_dengan_catatan_tautan_dan_berkas_wajib(): void
    {
        $tugas = Tugas::create([
            'id_peserta_magang' => $this->pesertaMagang->id_peserta_magang,
            'judul'             => 'Tugas Pengerjaan Database',
            'deskripsi'         => 'Buat skema DB dan migrasi.',
            'tenggat'           => now()->addDays(5)->toDateString(),
            'jumlah_progres'    => 2,
            'status'            => 'Ditugaskan',
        ]);

        $file = UploadedFile::fake()->create('laporan.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->pesertaUser)->post(route('peserta.tugas.progres', $tugas), [
            'catatan_progres' => 'Selesai membuat tabel users dan permissions.',
            'tautan_hasil'    => 'https://github.com/justfira/magangDTI/pull/1',
            'berkas'          => $file,
        ]);

        $response->assertSessionHas('sukses');
        $this->assertDatabaseHas('progres_tugas', [
            'id_tugas'        => $tugas->id_tugas,
            'urutan'          => 1,
            'catatan_progres' => 'Selesai membuat tabel users dan permissions.',
            'tautan_hasil'    => 'https://github.com/justfira/magangDTI/pull/1',
        ]);

        $tugas->refresh();
        $this->assertEquals('Diserahkan', $tugas->status);

        $progres = ProgresTugas::where('id_tugas', $tugas->id_tugas)->first();
        Storage::disk('local')->assertExists($progres->path_berkas);
    }

    public function test_peserta_tidak_bisa_mengirim_progres_saat_status_diserahkan(): void
    {
        $tugas = Tugas::create([
            'id_peserta_magang' => $this->pesertaMagang->id_peserta_magang,
            'judul'             => 'Tugas Review',
            'deskripsi'         => 'Sedang menunggu review.',
            'tenggat'           => now()->addDays(5)->toDateString(),
            'jumlah_progres'    => 2,
            'status'            => 'Diserahkan',
        ]);

        $file = UploadedFile::fake()->create('laporan.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->pesertaUser)->post(route('peserta.tugas.progres', $tugas), [
            'catatan_progres' => 'Coba kirim lagi.',
            'tautan_hasil'    => 'https://github.com/repo',
            'berkas'          => $file,
        ]);

        $response->assertSessionHas('gagal');
        $this->assertEquals(0, ProgresTugas::count());
    }

    public function test_mentor_review_diterima_pada_progres_antara_mengembalikan_status_ke_ditugaskan(): void
    {
        $tugas = Tugas::create([
            'id_peserta_magang' => $this->pesertaMagang->id_peserta_magang,
            'judul'             => 'Proyek Multi Tahap',
            'deskripsi'         => 'Tugas 2 tahapan.',
            'tenggat'           => now()->addDays(5)->toDateString(),
            'jumlah_progres'    => 2,
            'status'            => 'Diserahkan',
        ]);

        $progres = ProgresTugas::create([
            'id_tugas'        => $tugas->id_tugas,
            'urutan'          => 1,
            'catatan_progres' => 'Progres tahap 1 selesai.',
            'tautan_hasil'    => 'https://github.com/repo',
            'path_berkas'     => 'tugas-berkas/test.pdf',
            'waktu_kirim'     => now(),
        ]);

        $response = $this->actingAs($this->mentorUser)->post(route('mentor.progres.review', $progres), [
            'hasil_review'   => 'Diterima',
            'catatan_review' => 'Bagus, lanjutkan ke tahap 2.',
        ]);

        $response->assertSessionHas('sukses');
        $tugas->refresh();
        $this->assertEquals('Ditugaskan', $tugas->status);
        $this->assertEquals(2, $tugas->jumlah_progres);
    }

    public function test_mentor_review_diterima_pada_progres_terakhir_mengubah_status_menjadi_selesai(): void
    {
        $tugas = Tugas::create([
            'id_peserta_magang' => $this->pesertaMagang->id_peserta_magang,
            'judul'             => 'Proyek Single Tahap',
            'deskripsi'         => 'Tugas 1 tahapan.',
            'tenggat'           => now()->addDays(5)->toDateString(),
            'jumlah_progres'    => 1,
            'status'            => 'Diserahkan',
        ]);

        $progres = ProgresTugas::create([
            'id_tugas'        => $tugas->id_tugas,
            'urutan'          => 1,
            'catatan_progres' => 'Final release.',
            'tautan_hasil'    => 'https://github.com/repo',
            'path_berkas'     => 'tugas-berkas/test.pdf',
            'waktu_kirim'     => now(),
        ]);

        $response = $this->actingAs($this->mentorUser)->post(route('mentor.progres.review', $progres), [
            'hasil_review'   => 'Diterima',
            'catatan_review' => 'Lulus dan selesai.',
        ]);

        $response->assertSessionHas('sukses');
        $tugas->refresh();
        $this->assertEquals('Selesai', $tugas->status);
    }

    public function test_mentor_review_perlu_perbaikan_pada_progres_terakhir_menambah_jumlah_progres_dan_status_perlu_perbaikan(): void
    {
        $tugas = Tugas::create([
            'id_peserta_magang' => $this->pesertaMagang->id_peserta_magang,
            'judul'             => 'Proyek Tahap 1 Saja',
            'deskripsi'         => 'Tugas 1 tahapan.',
            'tenggat'           => now()->addDays(5)->toDateString(),
            'jumlah_progres'    => 1,
            'status'            => 'Diserahkan',
        ]);

        $progres = ProgresTugas::create([
            'id_tugas'        => $tugas->id_tugas,
            'urutan'          => 1,
            'catatan_progres' => 'Hasil pertama.',
            'tautan_hasil'    => 'https://github.com/repo',
            'path_berkas'     => 'tugas-berkas/test.pdf',
            'waktu_kirim'     => now(),
        ]);

        $response = $this->actingAs($this->mentorUser)->post(route('mentor.progres.review', $progres), [
            'hasil_review'   => 'Perlu Perbaikan',
            'catatan_review' => 'Mohon rapikan struktur kode sesuai PSR-12.',
        ]);

        $response->assertSessionHas('sukses');
        $tugas->refresh();
        $this->assertEquals('Perlu Perbaikan', $tugas->status);
        // Otomatis bertambah 1 dari 1 menjadi 2 agar peserta dapat mengirim revisi sebagai progres ke-2
        $this->assertEquals(2, $tugas->jumlah_progres);
    }

    public function test_unduh_berkas_berotentikasi_hanya_untuk_mentor_dan_peserta_terkait(): void
    {
        $tugas = Tugas::create([
            'id_peserta_magang' => $this->pesertaMagang->id_peserta_magang,
            'judul'             => 'Tugas Berkas',
            'deskripsi'         => 'Test unduh berkas.',
            'tenggat'           => now()->addDays(5)->toDateString(),
            'jumlah_progres'    => 1,
            'status'            => 'Ditugaskan',
        ]);

        Storage::disk('local')->put('tugas-berkas/1/dokumen_penting.pdf', 'konten rahasia');

        $progres = ProgresTugas::create([
            'id_tugas'        => $tugas->id_tugas,
            'urutan'          => 1,
            'catatan_progres' => 'Lampiran dokumen.',
            'tautan_hasil'    => 'https://github.com/repo',
            'path_berkas'     => 'tugas-berkas/1/dokumen_penting.pdf',
            'waktu_kirim'     => now(),
        ]);

        // 1. Mentor pembimbing bisa unduh
        $responseMentor = $this->actingAs($this->mentorUser)->get(route('tugas.progres.berkas', $progres));
        $responseMentor->assertOk();

        // 2. Peserta pemilik bisa unduh
        $responsePeserta = $this->actingAs($this->pesertaUser)->get(route('tugas.progres.berkas', $progres));
        $responsePeserta->assertOk();

        // 3. Pengguna lain ditolak (403)
        $userAsing = $this->buatAkun('PESERTA', 'peserta.asing@dti.test', 'Orang Asing');
        $responseAsing = $this->actingAs($userAsing)->get(route('tugas.progres.berkas', $progres));
        $responseAsing->assertForbidden();
    }

    public function test_mentor_dapat_melihat_halaman_edit_dan_memperbarui_tenggat_serta_tahapan_progres(): void
    {
        $tugas = Tugas::create([
            'id_peserta_magang' => $this->pesertaMagang->id_peserta_magang,
            'judul'             => 'Tugas Lama',
            'deskripsi'         => 'Deskripsi lama.',
            'tenggat'           => now()->addDays(3)->toDateString(),
            'jumlah_progres'    => 1,
            'judul_progres'     => ['Tahap Awal'],
            'status'            => 'Ditugaskan',
        ]);

        // 1. Halaman edit dapat diakses mentor
        $responseEdit = $this->actingAs($this->mentorUser)->get(route('mentor.tugas.edit', $tugas));
        $responseEdit->assertOk()
            ->assertSee('Ubah Tugas &amp; Tahapan Progres', false)
            ->assertSee('Tugas Lama');

        // 2. Mentor memperbarui judul, tenggat waktu, dan menambah tahapan progres menjadi 2
        $tenggatBaru = now()->addDays(14)->toDateString();
        $responseUpdate = $this->actingAs($this->mentorUser)->put(route('mentor.tugas.update', $tugas), [
            'judul'         => 'Tugas Diperbarui',
            'deskripsi'     => 'Deskripsi baru setelah perpanjangan.',
            'tenggat'       => $tenggatBaru,
            'judul_progres' => ['Tahap Awal Revisi', 'Tahap Pengujian Tambahan'],
        ]);

        $responseUpdate->assertRedirect(route('mentor.tugas.show', $tugas))
            ->assertSessionHas('sukses');

        $tugas->refresh();
        $this->assertEquals('Tugas Diperbarui', $tugas->judul);
        $this->assertEquals('Deskripsi baru setelah perpanjangan.', $tugas->deskripsi);
        $this->assertEquals($tenggatBaru, $tugas->tenggat->toDateString());
        $this->assertEquals(2, $tugas->jumlah_progres);
        $this->assertEquals(['Tahap Awal Revisi', 'Tahap Pengujian Tambahan'], $tugas->judul_progres);
    }

    public function test_mentor_dapat_menghapus_tugas(): void
    {
        $tugas = Tugas::create([
            'id_peserta_magang' => $this->pesertaMagang->id_peserta_magang,
            'judul'             => 'Tugas Yang Mau Dihapus',
            'deskripsi'         => 'Batal dikerjakan.',
            'tenggat'           => now()->addDays(3)->toDateString(),
            'jumlah_progres'    => 1,
            'judul_progres'     => ['Tahap 1'],
            'status'            => 'Ditugaskan',
        ]);

        $response = $this->actingAs($this->mentorUser)->delete(route('mentor.tugas.destroy', $tugas));
        $response->assertRedirect(route('mentor.tugas.index'))
            ->assertSessionHas('sukses', 'Tugas berhasil dihapus.');

        $this->assertDatabaseMissing('tugas', ['id_tugas' => $tugas->id_tugas]);
    }
}
