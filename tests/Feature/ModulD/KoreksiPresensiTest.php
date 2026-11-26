<?php

namespace Tests\Feature\ModulD;

use App\Models\KoreksiPresensi;
use App\Models\Pengguna;
use App\Models\Presensi;
use App\Models\PesertaMagang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** UC-10 A1 — Koreksi presensi oleh Superadmin */
class KoreksiPresensiTest extends TestCase
{
    use RefreshDatabase;

    private Presensi $presensi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
        // peserta lupa check-out kemarin
        $this->presensi = Presensi::create([
            'id_peserta_magang' => PesertaMagang::firstOrFail()->id_peserta_magang,
            'tanggal' => today()->toDateString(), 'sumber' => 'REGULER', 'waktu_checkin' => today()->setTime(8, 2),
        ]);
    }

    private function admin(): Pengguna
    {
        return Pengguna::where('email', 'superadmin@dti.test')->firstOrFail();
    }

    private function koreksi(array $ubah = [])
    {
        return $this->actingAs($this->admin())->post("/superadmin/presensi/{$this->presensi->id_presensi}/koreksi", array_merge([
            'jam_checkin' => '08:02', 'jam_checkout' => '16:30',
            'alasan' => 'Peserta lupa check-out karena gangguan jaringan.',
            'bukti' => UploadedFile::fake()->image('tangkapan-layar.png'),
        ], $ubah));
    }

    public function test_daftar_presensi_tampil(): void
    {
        $this->actingAs($this->admin())->get('/superadmin/presensi')->assertOk()->assertSee('Dimas Pratama')->assertSee('Belum check-out');
    }

    public function test_koreksi_tersimpan_beserta_riwayat_sebelum_sesudah(): void
    {
        $this->koreksi()->assertSessionHas('sukses');

        $p = $this->presensi->fresh();
        $this->assertSame('KOREKSI', $p->sumber);
        $this->assertSame('16:30', $p->waktu_checkout->format('H:i'));

        $k = KoreksiPresensi::firstOrFail();
        $this->assertNull($k->data_sebelum['waktu_checkout']);
        $this->assertSame('REGULER', $k->data_sebelum['sumber']);
        $this->assertStringEndsWith('16:30:00', $k->data_sesudah['waktu_checkout']);
        $this->assertSame($this->admin()->pegawai->id_pegawai, $k->id_pengoreksi);
        Storage::disk('local')->assertExists($k->path_bukti);

        $this->get("/superadmin/presensi/{$p->id_presensi}/koreksi")->assertOk()->assertSee('Riwayat koreksi')->assertSee('Admin DTI');
        $this->get("/superadmin/koreksi-presensi/{$k->id_koreksi}/bukti")->assertOk();
    }

    public function test_alasan_dan_bukti_wajib(): void
    {
        $this->koreksi(['alasan' => '', 'bukti' => null])->assertSessionHasErrors(['alasan', 'bukti']);
        $this->assertSame(0, KoreksiPresensi::count());
    }

    public function test_check_out_sebelum_check_in_ditolak(): void
    {
        $this->koreksi(['jam_checkin' => '10:00', 'jam_checkout' => '09:00'])->assertSessionHasErrors('jam_checkout');
        $this->assertNull($this->presensi->fresh()->waktu_checkout);
    }

    public function test_hanya_superadmin(): void
    {
        $mentor = Pengguna::where('email', 'mentor@dti.test')->firstOrFail();
        $this->actingAs($mentor)->get('/superadmin/presensi')->assertForbidden();
    }
}
