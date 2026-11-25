<?php

namespace Tests\Feature\ModulD;

use App\Models\Pengguna;
use App\Models\Presensi;
use App\Models\PesertaMagang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** UC-08 Presensi Reguler */
class PresensiRegulerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function peserta(): Pengguna
    {
        return Pengguna::where('email', 'peserta@mail.test')->firstOrFail();
    }

    private function lokasiKantor(): array
    {
        return ['latitude' => config('presensi.kantor.latitude'), 'longitude' => config('presensi.kantor.longitude'), 'akurasi' => 10];
    }

    public function test_halaman_presensi_tampil(): void
    {
        $this->actingAs($this->peserta())->get('/peserta/presensi')->assertOk()->assertSee('Belum check-in');
    }

    public function test_check_in_lalu_check_out_di_baris_yang_sama(): void
    {
        $this->actingAs($this->peserta());
        $this->post('/peserta/presensi/check-in', $this->lokasiKantor())->assertSessionHas('sukses');
        $this->post('/peserta/presensi/check-out', $this->lokasiKantor())->assertSessionHas('sukses');

        $this->assertSame(1, Presensi::count());
        $p = Presensi::first();
        $this->assertSame('REGULER', $p->sumber);
        $this->assertNotNull($p->waktu_checkin);
        $this->assertNotNull($p->waktu_checkout);
    }

    public function test_check_in_kedua_tidak_membuat_baris_baru(): void
    {
        $this->actingAs($this->peserta());
        $this->post('/peserta/presensi/check-in', $this->lokasiKantor());
        $this->post('/peserta/presensi/check-in', $this->lokasiKantor())->assertSessionHas('info');

        $this->assertSame(1, Presensi::count());
    }

    public function test_check_out_tanpa_check_in_ditolak(): void
    {
        $this->actingAs($this->peserta())->post('/peserta/presensi/check-out', $this->lokasiKantor())
            ->assertSessionHasErrors('presensi');
        $this->assertSame(0, Presensi::count());
    }

    public function test_peserta_tidak_aktif_ditolak(): void
    {
        PesertaMagang::query()->update(['status_magang' => 'Belum Mulai']);
        $this->actingAs($this->peserta())->post('/peserta/presensi/check-in', $this->lokasiKantor())
            ->assertSessionHasErrors('presensi');
        $this->assertSame(0, Presensi::count());
    }

    public function test_calon_peserta_tidak_bisa_membuka_presensi(): void
    {
        $calon = Pengguna::where('email', 'calon@mail.test')->firstOrFail();
        $this->actingAs($calon)->get('/peserta/presensi')->assertForbidden();
    }
}
