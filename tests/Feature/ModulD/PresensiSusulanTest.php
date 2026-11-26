<?php

namespace Tests\Feature\ModulD;

use App\Models\Notifikasi;
use App\Models\Pegawai;
use App\Models\PengajuanKehadiran;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Presensi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** UC-09 Mengajukan dan Memverifikasi Presensi Susulan */
class PresensiSusulanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function akun(string $email): Pengguna
    {
        return Pengguna::where('email', $email)->firstOrFail();
    }

    private function ajukan(array $ubah = [])
    {
        return $this->actingAs($this->akun('peserta@mail.test'))->post('/peserta/pengajuan-kehadiran', array_merge([
            'jenis' => 'SUSULAN',
            'tanggal_kegiatan' => today()->toDateString(),
            'alasan' => 'Mendampingi tim DTI instalasi jaringan di Fakultas Teknik.',
            'latitude' => config('presensi.kantor.latitude'),
            'longitude' => config('presensi.kantor.longitude'),
            'akurasi' => 10,
        ], $ubah));
    }

    public function test_peserta_mengajukan_susulan_dan_mentor_diberi_tahu(): void
    {
        $this->ajukan()->assertRedirect('/peserta/pengajuan-kehadiran')->assertSessionHas('sukses');

        $p = PengajuanKehadiran::firstOrFail();
        $this->assertSame('Menunggu Verifikasi', $p->status);
        $this->assertSame('SUSULAN', $p->jenis);
        $this->assertTrue(Notifikasi::where('id_pengguna', $this->akun('mentor@dti.test')->id_pengguna)
            ->where('jenis', 'PENGAJUAN_KEHADIRAN')->exists());
    }

    public function test_tanggal_bukan_hari_ini_ditolak(): void
    {
        $this->ajukan(['tanggal_kegiatan' => today()->subDay()->toDateString()])->assertSessionHasErrors('tanggal_kegiatan');
        $this->assertSame(0, PengajuanKehadiran::count());
    }

    public function test_alasan_wajib(): void
    {
        $this->ajukan(['alasan' => ''])->assertSessionHasErrors('alasan');
    }

    public function test_lokasi_di_luar_kantor_ditolak(): void
    {
        $this->ajukan(['latitude' => -0.95, 'longitude' => 100.35])->assertSessionHasErrors('pengajuan');
        $this->assertSame(0, PengajuanKehadiran::count());
    }

    public function test_sudah_hadir_ditolak(): void
    {
        $this->actingAs($this->akun('peserta@mail.test'))->post('/peserta/presensi/check-in',
            ['latitude' => config('presensi.kantor.latitude'), 'longitude' => config('presensi.kantor.longitude'), 'akurasi' => 10]);
        $this->ajukan()->assertSessionHasErrors('pengajuan');
        $this->assertSame(0, PengajuanKehadiran::count());
    }

    public function test_pengajuan_ganda_ditolak(): void
    {
        $this->ajukan();
        $this->ajukan()->assertSessionHasErrors('pengajuan');
        $this->assertSame(1, PengajuanKehadiran::count());
    }

    public function test_mentor_menyetujui_menghasilkan_satu_presensi_susulan(): void
    {
        $this->ajukan();
        $p = PengajuanKehadiran::firstOrFail();

        $this->actingAs($this->akun('mentor@dti.test'))->get('/mentor/verifikasi-kehadiran')->assertOk()->assertSee('Dimas Pratama');
        $this->post("/mentor/verifikasi-kehadiran/{$p->id_pengajuan_kehadiran}/setujui")->assertSessionHas('sukses');

        $this->assertSame('Disetujui', $p->fresh()->status);
        $presensi = Presensi::firstOrFail();
        $this->assertSame('SUSULAN', $presensi->sumber);
        $this->assertSame($p->id_pengajuan_kehadiran, $presensi->id_pengajuan_kehadiran);

        // disetujui dua kali tidak membuat presensi kedua
        $this->post("/mentor/verifikasi-kehadiran/{$p->id_pengajuan_kehadiran}/setujui")->assertSessionHasErrors('verifikasi');
        $this->assertSame(1, Presensi::count());
    }

    public function test_tolak_wajib_catatan_dan_tidak_membuat_presensi(): void
    {
        $this->ajukan();
        $p = PengajuanKehadiran::firstOrFail();
        $this->actingAs($this->akun('mentor@dti.test'));

        $this->post("/mentor/verifikasi-kehadiran/{$p->id_pengajuan_kehadiran}/tolak")->assertSessionHasErrors('catatan_keputusan');
        $this->post("/mentor/verifikasi-kehadiran/{$p->id_pengajuan_kehadiran}/tolak", ['catatan_keputusan' => 'Tidak ada penugasan luar kantor hari ini.'])
            ->assertSessionHas('sukses');

        $this->assertSame('Ditolak', $p->fresh()->status);
        $this->assertSame(0, Presensi::count());
    }

    public function test_mentor_lain_tidak_bisa_memverifikasi(): void
    {
        $this->ajukan();
        $p = PengajuanKehadiran::firstOrFail();
        $mentorLain = Pengguna::create(['id_peran' => Peran::where('kode_peran', 'MENTOR')->value('id_peran'), 'nama_lengkap' => 'Mentor Lain',
            'email' => 'lain@dti.test', 'password_hash' => Hash::make('password'), 'status_akun' => 'Aktif']);
        Pegawai::create(['id_pengguna' => $mentorLain->id_pengguna, 'nip' => '999']);

        $this->actingAs($mentorLain)->post("/mentor/verifikasi-kehadiran/{$p->id_pengajuan_kehadiran}/setujui")->assertForbidden();
        $this->actingAs($mentorLain)->get('/mentor/verifikasi-kehadiran')->assertOk()->assertDontSee('Dimas Pratama');
        $this->assertSame('Menunggu Verifikasi', $p->fresh()->status);
    }

    public function test_peserta_tidak_bisa_membuka_halaman_mentor(): void
    {
        $this->actingAs($this->akun('peserta@mail.test'))->get('/mentor/verifikasi-kehadiran')->assertForbidden();
    }
}
