<?php

namespace Tests\Feature\ModulD;

use App\Models\Notifikasi;
use App\Models\Pegawai;
use App\Models\PengajuanKehadiran;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Presensi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** UC-11 Memverifikasi Izin (sisi mentor) */
class PersetujuanIzinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    private function akun(string $email): Pengguna
    {
        return Pengguna::where('email', $email)->firstOrFail();
    }

    private function lokasi(): array
    {
        return ['latitude' => config('presensi.kantor.latitude'), 'longitude' => config('presensi.kantor.longitude'), 'akurasi' => 10];
    }

    private function ajukanIzin(string $jenisIzin = 'Penuh', ?UploadedFile $lampiran = null): PengajuanKehadiran
    {
        $this->actingAs($this->akun('peserta@mail.test'))->post('/peserta/pengajuan-kehadiran', array_filter([
            'jenis' => 'IZIN', 'tanggal_kegiatan' => today()->toDateString(), 'jenis_izin' => $jenisIzin,
            'alasan' => 'Sakit demam', 'lampiran' => $lampiran,
        ]))->assertSessionHas('sukses');

        return PengajuanKehadiran::latest('id_pengajuan_kehadiran')->firstOrFail();
    }

    private function setujui(PengajuanKehadiran $p)
    {
        return $this->actingAs($this->akun('mentor@dti.test'))->post("/mentor/verifikasi-kehadiran/{$p->id_pengajuan_kehadiran}/setujui",
            ['catatan_keputusan' => 'Semoga lekas sembuh']);
    }

    public function test_izin_disetujui_tidak_membuat_presensi_dan_peserta_diberi_tahu(): void
    {
        $p = $this->ajukanIzin();
        $this->setujui($p)->assertSessionHas('sukses');

        $this->assertSame('Disetujui', $p->fresh()->status);
        $this->assertSame(0, Presensi::count());                       // izin bukan hari hadir
        $this->assertTrue(Notifikasi::where('id_pengguna', $this->akun('peserta@mail.test')->id_pengguna)
            ->where('jenis', 'VERIFIKASI_KEHADIRAN')->exists());
    }

    public function test_izin_penuh_disetujui_memblokir_check_in(): void
    {
        $this->setujui($this->ajukanIzin('Penuh'));

        $this->actingAs($this->akun('peserta@mail.test'))->post('/peserta/presensi/check-in', $this->lokasi())
            ->assertSessionHasErrors('presensi');
        $this->assertSame(0, Presensi::count());
    }

    public function test_izin_sebagian_disetujui_tetap_boleh_check_in(): void
    {
        $this->setujui($this->ajukanIzin('Sebagian'));

        $this->actingAs($this->akun('peserta@mail.test'))->post('/peserta/presensi/check-in', $this->lokasi())
            ->assertSessionHas('sukses');
        $this->assertSame(1, Presensi::count());
    }

    public function test_persetujuan_ditolak_jika_peserta_ternyata_hadir(): void
    {
        $p = $this->ajukanIzin('Penuh');
        // izin masih menunggu, peserta ternyata datang dan check-in
        $this->actingAs($this->akun('peserta@mail.test'))->post('/peserta/presensi/check-in', $this->lokasi());

        $this->setujui($p)->assertSessionHasErrors('verifikasi');      // E2
        $this->assertSame('Menunggu Verifikasi', $p->fresh()->status);
    }

    public function test_mentor_bisa_melihat_lampiran_mentor_lain_tidak(): void
    {
        $p = $this->ajukanIzin('Penuh', UploadedFile::fake()->create('surat-dokter.pdf', 100, 'application/pdf'));

        $this->actingAs($this->akun('mentor@dti.test'))->get("/mentor/verifikasi-kehadiran/{$p->id_pengajuan_kehadiran}/lampiran")->assertOk();

        $lain = Pengguna::create(['id_peran' => Peran::where('kode_peran', 'MENTOR')->value('id_peran'), 'nama_lengkap' => 'Mentor Lain',
            'email' => 'lain@dti.test', 'password_hash' => Hash::make('password'), 'status_akun' => 'Aktif']);
        Pegawai::create(['id_pengguna' => $lain->id_pengguna, 'nip' => '999']);
        $this->actingAs($lain)->get("/mentor/verifikasi-kehadiran/{$p->id_pengajuan_kehadiran}/lampiran")->assertForbidden();
    }

    public function test_halaman_mentor_menampilkan_izin_dan_tautan_lampiran(): void
    {
        $this->ajukanIzin('Penuh', UploadedFile::fake()->create('surat.pdf', 50, 'application/pdf'));
        $this->actingAs($this->akun('mentor@dti.test'))->get('/mentor/verifikasi-kehadiran?jenis=IZIN')
            ->assertOk()->assertSee('Izin (Penuh)')->assertSee('Lihat lampiran');
    }
}
