<?php

namespace Tests\Feature\ModulD;

use App\Models\PengajuanKehadiran;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** UC-11 Mengajukan Izin (sisi peserta) */
class PengajuanIzinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
        $this->actingAs(Pengguna::where('email', 'peserta@mail.test')->firstOrFail());
    }

    private function ajukan(array $ubah = [])
    {
        return $this->post('/peserta/pengajuan-kehadiran', array_merge([
            'jenis' => 'IZIN',
            'tanggal_kegiatan' => today()->addDay()->toDateString(),
            'jenis_izin' => 'Penuh',
            'alasan' => 'Sakit demam',
        ], $ubah));
    }

    public function test_form_izin_tampil(): void
    {
        $this->get('/peserta/pengajuan-kehadiran/create?jenis=IZIN')->assertOk()->assertSee('Ajukan Izin');
    }

    public function test_izin_tanpa_lampiran_diterima(): void
    {
        $this->ajukan()->assertRedirect('/peserta/pengajuan-kehadiran')->assertSessionHas('sukses');
        $p = PengajuanKehadiran::firstOrFail();
        $this->assertSame(['IZIN', 'Penuh', 'Menunggu Verifikasi'], [$p->jenis, $p->jenis_izin, $p->status]);
        $this->assertNull($p->path_lampiran);
    }

    public function test_izin_dengan_lampiran_tersimpan_privat(): void
    {
        $this->ajukan(['lampiran' => UploadedFile::fake()->create('surat-dokter.pdf', 300, 'application/pdf')])->assertSessionHas('sukses');
        $p = PengajuanKehadiran::firstOrFail();
        $this->assertNotNull($p->path_lampiran);
        Storage::disk('local')->assertExists($p->path_lampiran);
    }

    public function test_lampiran_bukan_pdf_atau_gambar_ditolak(): void
    {
        $this->ajukan(['lampiran' => UploadedFile::fake()->create('virus.exe', 10)])->assertSessionHasErrors('lampiran');
        $this->assertSame(0, PengajuanKehadiran::count());
    }

    public function test_tanggal_di_luar_periode_magang_ditolak(): void
    {
        $this->ajukan(['tanggal_kegiatan' => today()->addYear()->toDateString()])->assertSessionHasErrors('pengajuan');
        $this->assertSame(0, PengajuanKehadiran::count());
    }

    public function test_alasan_wajib(): void
    {
        $this->ajukan(['alasan' => ''])->assertSessionHasErrors('alasan');
    }

    public function test_tanggal_yang_sudah_hadir_ditolak(): void
    {
        $this->post('/peserta/presensi/check-in',
            ['latitude' => config('presensi.kantor.latitude'), 'longitude' => config('presensi.kantor.longitude'), 'akurasi' => 10]);
        $this->ajukan(['tanggal_kegiatan' => today()->toDateString()])->assertSessionHasErrors('pengajuan');
    }

    public function test_izin_ganda_pada_tanggal_sama_ditolak(): void
    {
        $this->ajukan();
        $this->ajukan()->assertSessionHasErrors('pengajuan');
        $this->assertSame(1, PengajuanKehadiran::count());
    }
}
