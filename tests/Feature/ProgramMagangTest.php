<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\ProgramMagang;
use Database\Seeders\PeranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Pengujian UC-02 Mengelola Program Magang (FR-03): sisi Superadmin dan Calon Peserta. */
class ProgramMagangTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $admin;
    private Pegawai $pegawai;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PeranSeeder::class);
        $this->admin = $this->akun('SUPERADMIN', 'admin@dti.test');
        $this->pegawai = Pegawai::create(['id_pengguna' => $this->admin->id_pengguna]);
    }

    private function akun(string $kode, string $email): Pengguna
    {
        return Pengguna::create([
            'id_peran' => Peran::where('kode_peran', $kode)->value('id_peran'),
            'nama_lengkap' => 'Akun '.$kode, 'email' => $email,
            'password_hash' => Hash::make('password'), 'status_akun' => 'Aktif',
        ]);
    }

    private function data(array $timpa = []): array
    {
        return array_merge([
            'nama_program' => 'Magang DTI Periode 1',
            'tanggal_buka' => now()->subDay()->toDateString(),
            'tanggal_tutup' => now()->addDays(10)->toDateString(),
            'informasi' => 'Program magang DTI.',
            'persyaratan' => 'Mahasiswa aktif semester 5 ke atas.',
            'status_publikasi' => 'Dipublikasikan',
        ], $timpa);
    }

    private function program(array $timpa = []): ProgramMagang
    {
        return ProgramMagang::create(array_merge($this->data(), ['dibuat_oleh' => $this->pegawai->id_pegawai], $timpa));
    }

    // ------------------------------------------------------------ Superadmin

    public function test_superadmin_melihat_daftar_program_dengan_periode_dan_status(): void
    {
        $this->program(['nama_program' => 'Program Satu', 'status_publikasi' => 'Draft']);
        $this->program(['nama_program' => 'Program Dua', 'status_publikasi' => 'Ditutup']);

        $this->actingAs($this->admin)->get('/superadmin/program-magang')->assertOk()
            ->assertSee('Program Satu')->assertSee('Program Dua')
            ->assertSee('Draft')->assertSee('Ditutup')
            ->assertSee(now()->subDay()->format('d/m/Y'));
    }

    public function test_daftar_kosong_menampilkan_pesan(): void
    {
        $this->actingAs($this->admin)->get('/superadmin/program-magang')->assertOk()->assertSee('Belum ada program.');
    }

    public function test_menambah_program_baru_beserta_syarat_dan_notifikasi_sukses(): void
    {
        $this->actingAs($this->admin)->post('/superadmin/program-magang', $this->data(['syarat_baru' => "KTM\nCV\n\nTranskrip Nilai"]))
            ->assertRedirect(route('superadmin.program-magang.index'))
            ->assertSessionHas('sukses', 'Program berhasil disimpan.');

        $program = ProgramMagang::first();
        $this->assertSame('Magang DTI Periode 1', $program->nama_program);
        $this->assertSame($this->pegawai->id_pegawai, $program->dibuat_oleh);
        $this->assertSame(['KTM', 'CV', 'Transkrip Nilai'], $program->syaratDokumenProgram()->orderBy('urutan')->pluck('nama_syarat')->all());

        $this->get('/superadmin/program-magang')->assertSee('Program berhasil disimpan.', false);
    }

    public function test_semua_status_publikasi_dapat_disimpan(): void
    {
        foreach (['Dipublikasikan', 'Draft', 'Ditutup'] as $i => $status) {
            $this->actingAs($this->admin)->post('/superadmin/program-magang', $this->data(['nama_program' => "P{$i}", 'status_publikasi' => $status]))
                ->assertSessionHas('sukses');
            $this->assertSame($status, ProgramMagang::where('nama_program', "P{$i}")->value('status_publikasi'));
        }
    }

    public function test_mengubah_program_yang_ada(): void
    {
        $program = $this->program(['nama_program' => 'Lama']);

        $this->actingAs($this->admin)->get("/superadmin/program-magang/{$program->id_program}/edit")->assertOk()->assertSee('Lama');
        $this->put("/superadmin/program-magang/{$program->id_program}", $this->data(['nama_program' => 'Baru', 'status_publikasi' => 'Ditutup', 'syarat_baru' => 'Surat Pengantar']))
            ->assertRedirect(route('superadmin.program-magang.index'))
            ->assertSessionHas('sukses', 'Program berhasil disimpan.');

        $program->refresh();
        $this->assertSame('Baru', $program->nama_program);
        $this->assertSame('Ditutup', $program->status_publikasi);
        $this->assertSame(['Surat Pengantar'], $program->syaratDokumenProgram->pluck('nama_syarat')->all());
        $this->assertSame(1, ProgramMagang::count());
    }

    public function test_a1_data_kosong_ditolak_dengan_pesan_tunggal(): void
    {
        $respons = $this->actingAs($this->admin)->from('/superadmin/program-magang/create')
            ->post('/superadmin/program-magang', ['nama_program' => '', 'tanggal_buka' => '', 'tanggal_tutup' => '', 'status_publikasi' => ''])
            ->assertRedirect('/superadmin/program-magang/create')
            ->assertSessionHasErrors(['nama_program', 'tanggal_buka', 'tanggal_tutup', 'status_publikasi']);

        $this->assertSame(0, ProgramMagang::count());
        $this->assertSame('Data belum lengkap atau periode tidak valid.', session('errors')->first('nama_program'));

        // Pesan yang sama tidak diulang-ulang di halaman form.
        $halaman = $this->followingRedirects()->get('/superadmin/program-magang/create')->getContent();
        $this->assertSame(1, substr_count($halaman, 'Data belum lengkap atau periode tidak valid.'));
    }

    public function test_a1_tanggal_tutup_lebih_awal_dari_tanggal_buka_ditolak(): void
    {
        $this->actingAs($this->admin)->post('/superadmin/program-magang', $this->data([
            'tanggal_buka' => '2026-12-10', 'tanggal_tutup' => '2026-12-01',
        ]))->assertSessionHasErrors('tanggal_tutup');

        $this->assertStringContainsString('Data belum lengkap atau periode tidak valid', session('errors')->first('tanggal_tutup'));
        $this->assertSame(0, ProgramMagang::count());
    }

    public function test_tanggal_tutup_sama_dengan_tanggal_buka_diperbolehkan(): void
    {
        $this->actingAs($this->admin)->post('/superadmin/program-magang', $this->data([
            'tanggal_buka' => '2026-12-10', 'tanggal_tutup' => '2026-12-10',
        ]))->assertSessionHasNoErrors();
        $this->assertSame(1, ProgramMagang::count());
    }

    public function test_status_publikasi_tidak_dikenal_ditolak(): void
    {
        $this->actingAs($this->admin)->post('/superadmin/program-magang', $this->data(['status_publikasi' => 'Rahasia']))
            ->assertSessionHasErrors('status_publikasi');
    }

    public function test_e1_gagal_menyimpan_menampilkan_pesan_dan_form_tetap_terisi(): void
    {
        Schema::drop('syarat_dokumen_program');   // meniru gangguan database di tengah transaksi

        $this->actingAs($this->admin)->from('/superadmin/program-magang/create')
            ->post('/superadmin/program-magang', $this->data(['nama_program' => 'Tidak Boleh Tersimpan', 'syarat_baru' => 'KTM']))
            ->assertRedirect('/superadmin/program-magang/create')
            ->assertSessionHas('gagal', 'Gagal menyimpan program, silakan coba lagi.')
            ->assertSessionHasInput('nama_program', 'Tidak Boleh Tersimpan');

        $this->assertSame(0, ProgramMagang::count(), 'Transaksi harus dibatalkan: program tidak boleh setengah tersimpan.');
    }

    public function test_e1_juga_berlaku_saat_mengubah_program(): void
    {
        $program = $this->program(['nama_program' => 'Asli']);
        Schema::drop('syarat_dokumen_program');

        $this->actingAs($this->admin)->put("/superadmin/program-magang/{$program->id_program}", $this->data(['nama_program' => 'Diubah', 'syarat_baru' => 'KTM']))
            ->assertSessionHas('gagal', 'Gagal menyimpan program, silakan coba lagi.');

        $this->assertSame('Asli', $program->fresh()->nama_program);
    }

    public function test_hanya_superadmin_boleh_mengelola_program(): void
    {
        foreach (['MENTOR', 'KEPALA_SEKSI', 'DIREKTUR', 'PESERTA'] as $kode) {
            $user = $this->akun($kode, strtolower($kode).'@dti.test');
            $this->actingAs($user)->get('/superadmin/program-magang')->assertForbidden();
            $this->post('/superadmin/program-magang', $this->data())->assertForbidden();
            auth()->logout();
        }
        $this->assertSame(0, ProgramMagang::count());
        $this->get('/superadmin/program-magang')->assertRedirect('/login');
    }

    // ------------------------------------------------------------ Calon Peserta

    public function test_calon_peserta_melihat_program_dipublikasikan_yang_sedang_dibuka(): void
    {
        $dibuka = $this->program(['nama_program' => 'Program Dibuka']);
        $dibuka->syaratDokumenProgram()->create(['nama_syarat' => 'KTM', 'wajib' => true, 'urutan' => 1]);

        $this->get('/')->assertOk()
            ->assertSee('Program Dibuka')
            ->assertSee('Mahasiswa aktif semester 5 ke atas.')   // persyaratan (teks)
            ->assertSee('KTM')                                   // syarat dokumen
            ->assertSee(now()->subDay()->translatedFormat('d M Y'))
            ->assertSee(now()->addDays(10)->translatedFormat('d M Y'));
    }

    public function test_program_draft_ditutup_belum_dibuka_dan_kedaluwarsa_tidak_tampil(): void
    {
        $this->program(['nama_program' => 'Prog Draft', 'status_publikasi' => 'Draft']);
        $this->program(['nama_program' => 'Prog Ditutup', 'status_publikasi' => 'Ditutup']);
        $this->program(['nama_program' => 'Prog Belum Mulai', 'tanggal_buka' => now()->addDays(3)->toDateString(), 'tanggal_tutup' => now()->addDays(9)->toDateString()]);
        $this->program(['nama_program' => 'Prog Kedaluwarsa', 'tanggal_buka' => now()->subDays(20)->toDateString(), 'tanggal_tutup' => now()->subDays(2)->toDateString()]);

        $this->get('/')->assertOk()
            ->assertDontSee('Prog Draft')->assertDontSee('Prog Ditutup')
            ->assertDontSee('Prog Belum Mulai')->assertDontSee('Prog Kedaluwarsa');
    }

    public function test_program_yang_tutup_hari_ini_masih_tampil(): void
    {
        $this->program(['nama_program' => 'Tutup Hari Ini', 'tanggal_tutup' => today()->toDateString()]);
        $this->get('/')->assertSee('Tutup Hari Ini');
    }

    public function test_tombol_daftar_sekarang_mengarah_ke_uc03(): void
    {
        $program = $this->program();

        $this->get('/')->assertSee('Daftar Sekarang')->assertSee(route('pendaftaran.create', $program), false);
        $this->get(route('pendaftaran.create', $program))->assertOk();
    }

    public function test_a2_tidak_ada_program_dibuka(): void
    {
        $this->get('/')->assertOk()->assertSee('Belum ada program rekrutmen yang dibuka')->assertDontSee('Daftar Sekarang');
    }

    public function test_e2_gagal_memuat_informasi(): void
    {
        Schema::drop('program_magang');

        $this->get('/')->assertOk()
            ->assertSee('Informasi tidak dapat dimuat, silakan coba lagi.')
            ->assertDontSee('Belum ada program rekrutmen yang dibuka');
    }

    public function test_halaman_informasi_dapat_dibuka_tanpa_login(): void
    {
        $this->assertGuest();
        $this->get('/')->assertOk();
    }
}
