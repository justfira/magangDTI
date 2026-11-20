<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Peran;
use Database\Seeders\PeranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Pengujian UC-01 Melakukan Login dan Logout (FR-01). */
class LoginLogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PeranSeeder::class);
    }

    private function buatAkun(string $kodePeran = 'SUPERADMIN', string $status = 'Aktif', string $email = 'user@dti.test'): Pengguna
    {
        return Pengguna::create([
            'id_peran' => Peran::where('kode_peran', $kodePeran)->value('id_peran'),
            'nama_lengkap' => 'Pengguna Uji',
            'email' => $email,
            'password_hash' => Hash::make('rahasia123'),
            'status_akun' => $status,
        ]);
    }

    public function test_halaman_login_bisa_dibuka(): void
    {
        $this->get('/login')->assertOk()->assertSee('Login');
    }

    public function test_login_berhasil_mengarah_ke_dashboard(): void
    {
        $akun = $this->buatAkun();

        $this->post('/login', ['email' => $akun->email, 'password' => 'rahasia123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($akun);
    }

    public function test_input_kosong_ditolak_dengan_pesan(): void
    {
        $this->post('/login', ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
        $this->assertSame('Email dan password wajib diisi.', session('errors')->first('email'));
    }

    public function test_password_kosong_ditolak(): void
    {
        $akun = $this->buatAkun();

        $this->post('/login', ['email' => $akun->email, 'password' => ''])
            ->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_password_salah_ditolak(): void
    {
        $akun = $this->buatAkun();

        $this->from('/login')->post('/login', ['email' => $akun->email, 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);
        $this->assertGuest();
    }

    public function test_email_tidak_terdaftar_ditolak_dengan_pesan_yang_sama(): void
    {
        $this->post('/login', ['email' => 'tidakada@dti.test', 'password' => 'rahasia123'])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);
        $this->assertGuest();
    }

    public function test_akun_nonaktif_ditolak(): void
    {
        $akun = $this->buatAkun('MENTOR', 'Nonaktif');

        $this->post('/login', ['email' => $akun->email, 'password' => 'rahasia123'])
            ->assertSessionHasErrors(['email' => 'Akun nonaktif, hubungi Superadmin.']);
        $this->assertGuest();
    }

    public function test_dashboard_menyesuaikan_peran(): void
    {
        $judul = [
            'SUPERADMIN' => 'Dashboard Superadmin',
            'KEPALA_SEKSI' => 'Dashboard Kepala Seksi',
            'DIREKTUR' => 'Dashboard Direktur',
            'MENTOR' => 'Dashboard Mentor',
            'PESERTA' => 'Dashboard Calon Peserta',   // belum diterima = calon peserta
        ];

        foreach ($judul as $kode => $teks) {
            $akun = $this->buatAkun($kode, 'Aktif', strtolower($kode).'@dti.test');
            if ($kode === 'MENTOR') {
                \App\Models\Pegawai::create(['id_pengguna' => $akun->id_pengguna, 'nip' => '1990', 'id_seksi' => null]);
            }

            $this->actingAs($akun)->get('/dashboard')->assertOk()->assertSee($teks);
            auth()->logout();
        }
    }

    public function test_akun_dinonaktifkan_saat_login_langsung_dikeluarkan(): void
    {
        $akun = $this->buatAkun();
        $this->actingAs($akun);
        $akun->update(['status_akun' => 'Nonaktif']);

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_logout_mengakhiri_sesi_dan_kembali_ke_login(): void
    {
        $akun = $this->buatAkun();

        $this->actingAs($akun)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_pengguna_yang_sudah_login_tidak_bisa_membuka_halaman_login(): void
    {
        $this->actingAs($this->buatAkun())->get('/login')->assertRedirect(route('dashboard'));
    }
}
