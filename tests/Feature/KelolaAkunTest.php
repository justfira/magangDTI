<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Seksi;
use Database\Seeders\PeranSeeder;
use Database\Seeders\SuperadminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Pengujian Manajemen Akun & Hak Akses oleh Superadmin (UC-17). */
class KelolaAkunTest extends TestCase
{
    use RefreshDatabase;

    private Seksi $seksi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PeranSeeder::class);
        $this->seksi = Seksi::create(['nama_seksi' => 'Pengembangan Sistem']);
    }

    private function akun(string $kode, string $email, string $status = 'Aktif'): Pengguna
    {
        $p = Pengguna::create([
            'id_peran' => Peran::where('kode_peran', $kode)->value('id_peran'),
            'nama_lengkap' => 'Akun '.$kode,
            'email' => $email,
            'password_hash' => Hash::make('password'),
            'status_akun' => $status,
        ]);
        if ($kode !== 'PESERTA') {
            Pegawai::create(['id_pengguna' => $p->id_pengguna, 'id_seksi' => in_array($kode, ['MENTOR', 'KEPALA_SEKSI']) ? $this->seksi->id_seksi : null]);
        }

        return $p;
    }

    private function dataBaru(array $timpa = []): array
    {
        return array_merge([
            'nama_lengkap' => 'Budi Mentor', 'email' => 'budi@dti.test',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
            'kode_peran' => 'MENTOR', 'id_seksi' => $this->seksi->id_seksi, 'nip' => '1990001', 'jabatan' => 'Staf',
        ], $timpa);
    }

    public function test_superadmin_membuat_akun_untuk_mentor_kasi_dan_direktur(): void
    {
        $admin = $this->akun('SUPERADMIN', 'admin@dti.test');

        foreach (['MENTOR', 'KEPALA_SEKSI', 'DIREKTUR'] as $i => $kode) {
            $this->actingAs($admin)->post('/superadmin/akun', $this->dataBaru([
                'email' => "u{$i}@dti.test", 'kode_peran' => $kode, 'nip' => "99{$i}",
            ]))->assertRedirect(route('superadmin.akun.index'));

            $baru = Pengguna::where('email', "u{$i}@dti.test")->first();
            $this->assertSame($kode, $baru->kodePeran());
            $this->assertSame('Aktif', $baru->status_akun);
            $this->assertTrue(Hash::check('rahasia123', $baru->password_hash));
            $this->assertNotSame('rahasia123', $baru->password_hash);
            $this->assertNotNull($baru->pegawai);
        }

        // Direktur tidak membawa seksi meski dikirim.
        $this->assertNull(Pengguna::where('email', 'u2@dti.test')->first()->pegawai->id_seksi);
    }

    public function test_akun_baru_langsung_bisa_login_dan_masuk_dashboard_sesuai_peran(): void
    {
        $admin = $this->akun('SUPERADMIN', 'admin@dti.test');
        $this->actingAs($admin)->post('/superadmin/akun', $this->dataBaru(['kode_peran' => 'DIREKTUR']));
        auth()->logout();

        $this->post('/login', ['email' => 'budi@dti.test', 'password' => 'rahasia123'])->assertRedirect(route('dashboard'));
        $this->get('/dashboard')->assertOk()->assertSee('Dashboard Direktur');
    }

    public function test_validasi_email_unik_seksi_dan_sandi(): void
    {
        $admin = $this->akun('SUPERADMIN', 'admin@dti.test');
        $this->akun('MENTOR', 'ada@dti.test');

        $this->actingAs($admin)->post('/superadmin/akun', $this->dataBaru(['email' => 'ada@dti.test']))
            ->assertSessionHasErrors(['email' => 'Email sudah terdaftar.']);
        $this->post('/superadmin/akun', $this->dataBaru(['id_seksi' => '']))->assertSessionHasErrors('id_seksi');
        $this->post('/superadmin/akun', $this->dataBaru(['password' => 'pendek', 'password_confirmation' => 'pendek']))->assertSessionHasErrors('password');
        $this->post('/superadmin/akun', $this->dataBaru(['password_confirmation' => 'beda']))->assertSessionHasErrors('password');
        $this->post('/superadmin/akun', $this->dataBaru(['kode_peran' => 'PESERTA']))->assertSessionHasErrors('kode_peran');
        $this->post('/superadmin/akun', $this->dataBaru(['nama_lengkap' => '']))->assertSessionHasErrors('nama_lengkap');
    }

    public function test_hanya_superadmin_yang_boleh_mengelola_akun(): void
    {
        foreach (['MENTOR', 'KEPALA_SEKSI', 'DIREKTUR', 'PESERTA'] as $kode) {
            $user = $this->akun($kode, strtolower($kode).'@dti.test');
            $this->actingAs($user)->get('/superadmin/akun')->assertForbidden();
            $this->post('/superadmin/akun', $this->dataBaru())->assertForbidden();
            $this->get('/superadmin/hak-akses')->assertForbidden();
            auth()->logout();
        }
        $this->get('/superadmin/akun')->assertRedirect('/login');
    }

    public function test_superadmin_mengubah_peran_sebagai_pengaturan_hak_akses(): void
    {
        $admin = $this->akun('SUPERADMIN', 'admin@dti.test');
        $mentor = $this->akun('MENTOR', 'm@dti.test');

        $this->actingAs($admin)->put("/superadmin/akun/{$mentor->id_pengguna}", $this->dataBaru([
            'email' => 'm@dti.test', 'kode_peran' => 'KEPALA_SEKSI', 'password' => '', 'password_confirmation' => '',
        ]))->assertRedirect(route('superadmin.akun.index'));

        $this->assertSame('KEPALA_SEKSI', $mentor->fresh()->kodePeran());
        // Kata sandi lama tetap berlaku bila field dikosongkan.
        $this->assertTrue(Hash::check('password', $mentor->fresh()->password_hash));

        // Hak akses benar-benar berubah: kini boleh ke halaman Kasi, tidak lagi ke halaman Mentor.
        auth()->logout();
        $this->actingAs($mentor->fresh())->get('/kepala-seksi/penempatan')->assertOk();
        $this->get('/mentor/tugas')->assertForbidden();
    }

    public function test_reset_kata_sandi_lewat_ubah_akun(): void
    {
        $admin = $this->akun('SUPERADMIN', 'admin@dti.test');
        $mentor = $this->akun('MENTOR', 'm@dti.test');

        $this->actingAs($admin)->put("/superadmin/akun/{$mentor->id_pengguna}", $this->dataBaru([
            'email' => 'm@dti.test', 'password' => 'baru12345', 'password_confirmation' => 'baru12345',
        ]));

        $this->assertTrue(Hash::check('baru12345', $mentor->fresh()->password_hash));
    }

    public function test_tidak_bisa_mengubah_peran_sendiri_atau_superadmin_terakhir(): void
    {
        $admin = $this->akun('SUPERADMIN', 'admin@dti.test');

        $this->actingAs($admin)->from('/x')->put("/superadmin/akun/{$admin->id_pengguna}", $this->dataBaru([
            'email' => 'admin@dti.test', 'kode_peran' => 'MENTOR',
        ]))->assertSessionHasErrors('kode_peran');
        $this->assertSame('SUPERADMIN', $admin->fresh()->kodePeran());

        // Superadmin lain mencoba menurunkan satu-satunya superadmin aktif lain yang tersisa.
        $lain = $this->akun('SUPERADMIN', 'lain@dti.test');
        $admin->update(['status_akun' => 'Nonaktif']);
        $this->actingAs($this->akun('SUPERADMIN', 'ketiga@dti.test', 'Nonaktif'));
        $this->assertSame(1, Pengguna::where('status_akun', 'Aktif')->whereHas('peran', fn ($q) => $q->where('kode_peran', 'SUPERADMIN'))->count());
        $this->actingAs($lain)->put("/superadmin/akun/{$lain->id_pengguna}", $this->dataBaru(['email' => 'lain@dti.test', 'kode_peran' => 'DIREKTUR']))
            ->assertSessionHasErrors('kode_peran');
    }

    public function test_mentor_yang_masih_membimbing_tidak_boleh_diganti_peran(): void
    {
        $admin = $this->akun('SUPERADMIN', 'admin@dti.test');
        $mentor = $this->akun('MENTOR', 'm@dti.test');
        $peserta = $this->akun('PESERTA', 'p@mail.test');

        // Rantai keputusan->pengajuan tidak relevan di sini: cek FK ditunda sampai commit (tidak pernah terjadi, tes di-rollback).
        DB::statement('PRAGMA defer_foreign_keys = ON');
        DB::table('peserta_magang')->insert([
            'id_keputusan' => 1, 'id_pengguna' => $peserta->id_pengguna, 'id_mentor' => $mentor->pegawai->id_pegawai,
            'tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => '2026-03-01', 'status_magang' => 'Aktif',
        ]);

        $this->actingAs($admin)->put("/superadmin/akun/{$mentor->id_pengguna}", $this->dataBaru([
            'email' => 'm@dti.test', 'kode_peran' => 'DIREKTUR',
        ]))->assertSessionHasErrors('kode_peran');
        $this->assertSame('MENTOR', $mentor->fresh()->kodePeran());
    }

    public function test_nonaktifkan_dan_aktifkan_kembali_akun(): void
    {
        $admin = $this->akun('SUPERADMIN', 'admin@dti.test');
        $mentor = $this->akun('MENTOR', 'm@dti.test');

        $this->actingAs($admin)->post("/superadmin/akun/{$mentor->id_pengguna}/nonaktifkan")->assertSessionHas('sukses');
        $this->assertSame('Nonaktif', $mentor->fresh()->status_akun);

        auth()->logout();
        $this->post('/login', ['email' => 'm@dti.test', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Akun nonaktif, hubungi Superadmin.']);

        $this->actingAs($admin)->post("/superadmin/akun/{$mentor->id_pengguna}/aktifkan")->assertSessionHas('sukses');
        auth()->logout();
        $this->post('/login', ['email' => 'm@dti.test', 'password' => 'password'])->assertRedirect(route('dashboard'));
    }

    public function test_sesi_akun_yang_dinonaktifkan_langsung_berakhir(): void
    {
        $admin = $this->akun('SUPERADMIN', 'admin@dti.test');
        $mentor = $this->akun('MENTOR', 'm@dti.test');

        $this->actingAs($admin)->post("/superadmin/akun/{$mentor->id_pengguna}/nonaktifkan");
        auth()->logout();

        $this->actingAs($mentor->fresh())->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_tidak_bisa_menonaktifkan_diri_sendiri(): void
    {
        $admin = $this->akun('SUPERADMIN', 'admin@dti.test');

        $this->actingAs($admin)->post("/superadmin/akun/{$admin->id_pengguna}/nonaktifkan")->assertSessionHas('gagal');
        $this->assertSame('Aktif', $admin->fresh()->status_akun);
    }

    public function test_tidak_bisa_menonaktifkan_superadmin_aktif_terakhir(): void
    {
        $admin = $this->akun('SUPERADMIN', 'admin@dti.test');
        $this->akun('SUPERADMIN', 'nonaktif@dti.test', 'Nonaktif');
        $kedua = $this->akun('SUPERADMIN', 'kedua@dti.test');
        $kedua->update(['status_akun' => 'Nonaktif']);
        $kedua->update(['status_akun' => 'Aktif']);

        // Dua superadmin aktif: boleh. Setelah satu nonaktif, yang terakhir dilindungi.
        $this->actingAs($admin)->post("/superadmin/akun/{$kedua->id_pengguna}/nonaktifkan")->assertSessionHas('sukses');
        $this->actingAs($kedua)->post("/superadmin/akun/{$admin->id_pengguna}/nonaktifkan");
        $this->assertSame('Aktif', $admin->fresh()->status_akun);
    }

    public function test_daftar_akun_dan_filter(): void
    {
        $admin = $this->akun('SUPERADMIN', 'admin@dti.test');
        $this->akun('MENTOR', 'mentor-satu@dti.test');
        $this->akun('DIREKTUR', 'direktur-satu@dti.test');

        $this->actingAs($admin)->get('/superadmin/akun')->assertOk()->assertSee('mentor-satu@dti.test')->assertSee('direktur-satu@dti.test');
        $this->get('/superadmin/akun?peran=MENTOR')->assertSee('mentor-satu@dti.test')->assertDontSee('direktur-satu@dti.test');
        $this->get('/superadmin/akun?cari=direktur-satu')->assertSee('direktur-satu@dti.test')->assertDontSee('mentor-satu@dti.test');
    }

    public function test_halaman_ubah_hanya_untuk_akun_pegawai(): void
    {
        $admin = $this->akun('SUPERADMIN', 'admin@dti.test');
        $peserta = $this->akun('PESERTA', 'p@mail.test');

        $this->actingAs($admin)->get("/superadmin/akun/{$peserta->id_pengguna}/edit")->assertNotFound();
        $this->get('/superadmin/akun/create')->assertOk();
        $this->get("/superadmin/akun/{$admin->id_pengguna}/edit")->assertOk()->assertSee('tidak dapat diubah');
    }

    public function test_halaman_hak_akses_menampilkan_menu_tiap_peran(): void
    {
        $admin = $this->akun('SUPERADMIN', 'admin@dti.test');

        $this->actingAs($admin)->get('/superadmin/hak-akses')->assertOk()
            ->assertSee('Manajemen Akun')->assertSee('Persetujuan Penerimaan')->assertSee('Periksa Logbook');
    }

    public function test_seeder_superadmin_dari_env(): void
    {
        putenv('SUPERADMIN_EMAIL=awal@dti.test');
        putenv('SUPERADMIN_PASSWORD=sandiawal123');
        $_ENV['SUPERADMIN_EMAIL'] = 'awal@dti.test';
        $_ENV['SUPERADMIN_PASSWORD'] = 'sandiawal123';

        $this->seed(SuperadminSeeder::class);
        $this->seed(SuperadminSeeder::class);   // idempotent

        $this->assertSame(1, Pengguna::where('email', 'awal@dti.test')->count());
        $akun = Pengguna::where('email', 'awal@dti.test')->first();
        $this->assertSame('SUPERADMIN', $akun->kodePeran());
        $this->post('/login', ['email' => 'awal@dti.test', 'password' => 'sandiawal123'])->assertRedirect(route('dashboard'));
        $this->get('/dashboard')->assertSee('Dashboard Superadmin');

        putenv('SUPERADMIN_EMAIL');
        putenv('SUPERADMIN_PASSWORD');
        unset($_ENV['SUPERADMIN_EMAIL'], $_ENV['SUPERADMIN_PASSWORD']);
    }
}
