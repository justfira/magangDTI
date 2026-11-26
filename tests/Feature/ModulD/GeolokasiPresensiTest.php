<?php

namespace Tests\Feature\ModulD;

use App\Models\Pengguna;
use App\Models\Presensi;
use App\Services\GeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** UC-08 E1/E2 — validasi lokasi presensi */
class GeolokasiPresensiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['presensi.kantor' => ['nama' => 'Kantor DTI', 'latitude' => -0.9147, 'longitude' => 100.4583,
            'radius_meter' => 100, 'batas_akurasi_meter' => 50]]);
        $this->actingAs(Pengguna::where('email', 'peserta@mail.test')->firstOrFail());
    }

    public function test_tanpa_lokasi_ditolak(): void
    {
        $this->post('/peserta/presensi/check-in')->assertSessionHasErrors('presensi');
        $this->assertSame(0, Presensi::count());
    }

    public function test_di_luar_radius_ditolak(): void
    {
        // ± 1,1 km ke utara dari kantor
        $this->post('/peserta/presensi/check-in', ['latitude' => -0.9047, 'longitude' => 100.4583, 'akurasi' => 10])
            ->assertSessionHasErrors('presensi');
        $this->assertSame(0, Presensi::count());
    }

    public function test_akurasi_terlalu_rendah_ditolak(): void
    {
        $this->post('/peserta/presensi/check-in', ['latitude' => -0.9147, 'longitude' => 100.4583, 'akurasi' => 300])
            ->assertSessionHasErrors('presensi');
        $this->assertSame(0, Presensi::count());
    }

    public function test_di_dalam_radius_diterima_dan_koordinat_tersimpan(): void
    {
        $this->post('/peserta/presensi/check-in', ['latitude' => -0.9150, 'longitude' => 100.4585, 'akurasi' => 12])
            ->assertSessionHas('sukses');
        $p = Presensi::firstOrFail();
        $this->assertEquals(-0.915, (float) $p->lat_checkin);
        $this->assertEquals(12, (float) $p->akurasi_checkin);
    }

    public function test_rumus_jarak_haversine(): void
    {
        $jarak = app(GeofenceService::class)->jarakMeter(-0.9147, 100.4583, -0.9047, 100.4583);
        $this->assertEqualsWithDelta(1112, $jarak, 5);   // 0,01 derajat lintang ≈ 1,11 km
    }
}
