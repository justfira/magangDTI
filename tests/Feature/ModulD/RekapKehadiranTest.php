<?php

namespace Tests\Feature\ModulD;

use App\Models\PengajuanKehadiran;
use App\Models\Pengguna;
use App\Models\Presensi;
use App\Models\PesertaMagang;
use App\Services\KehadiranService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** UC-10 Rekap kehadiran bulanan & ekspor CSV */
class RekapKehadiranTest extends TestCase
{
    use RefreshDatabase;

    private PesertaMagang $peserta;
    private Carbon $bulan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bulan = Carbon::create(2026, 11, 1);
        $this->peserta = PesertaMagang::firstOrFail();
        $this->peserta->update(['tanggal_mulai' => '2026-10-01', 'tanggal_selesai' => '2027-01-31']);

        $hari = fn (int $d) => $this->bulan->copy()->day($d);
        $buat = fn (int $d, string $sumber, bool $checkout = true, ?int $idPengajuan = null) => Presensi::create([
            'id_peserta_magang' => $this->peserta->id_peserta_magang, 'tanggal' => $hari($d)->toDateString(), 'sumber' => $sumber,
            'id_pengajuan_kehadiran' => $idPengajuan,
            'waktu_checkin' => $hari($d)->setTime(8, 0), 'waktu_checkout' => $checkout ? $hari($d)->setTime(16, 0) : null,
        ]);
        $pengajuan = fn (int $d, string $jenis, ?string $jenisIzin, string $status) => PengajuanKehadiran::create([
            'id_peserta_magang' => $this->peserta->id_peserta_magang, 'jenis' => $jenis, 'jenis_izin' => $jenisIzin,
            'tanggal_kegiatan' => $hari($d)->toDateString(), 'alasan' => 'uji', 'status' => $status, 'waktu_pengajuan' => $hari($d),
            'latitude' => $jenis === 'SUSULAN' ? -0.9147 : null, 'longitude' => $jenis === 'SUSULAN' ? 100.4583 : null,
            'akurasi_meter' => $jenis === 'SUSULAN' ? 10 : null,
        ]);

        $buat(2, 'REGULER');                     // hadir
        $buat(3, 'REGULER');                     // hadir
        $buat(4, 'REGULER', checkout: false);    // tidak dihitung (belum check-out)
        $buat(5, 'SUSULAN', idPengajuan: $pengajuan(5, 'SUSULAN', null, 'Disetujui')->id_pengajuan_kehadiran);   // hadir
        $buat(6, 'KOREKSI');                     // hadir
        $pengajuan(9, 'IZIN', 'Penuh', 'Disetujui');      // izin penuh
        $pengajuan(10, 'IZIN', 'Penuh', 'Ditolak');       // tidak dihitung
        $pengajuan(11, 'IZIN', 'Sebagian', 'Disetujui');  // izin sebagian
        $buat(1, 'REGULER');                     // hadir
        // presensi bulan lain tidak ikut
        Presensi::create(['id_peserta_magang' => $this->peserta->id_peserta_magang, 'tanggal' => '2026-10-31', 'sumber' => 'REGULER',
            'waktu_checkin' => '2026-10-31 08:00:00', 'waktu_checkout' => '2026-10-31 16:00:00']);
    }

    public function test_perhitungan_rekap(): void
    {
        $r = app(KehadiranService::class)->rekapBulanan(2026, 11)->firstWhere('id_peserta_magang', $this->peserta->id_peserta_magang);

        $this->assertSame(5, (int) $r->hadir);            // 1, 2, 3 (reguler) + 5 (susulan) + 6 (koreksi)
        $this->assertSame(1, (int) $r->hadir_susulan);
        $this->assertSame(1, (int) $r->belum_checkout);
        $this->assertSame(1, (int) $r->izin_penuh);
        $this->assertSame(1, (int) $r->izin_sebagian);
    }

    public function test_halaman_rekap_dan_ekspor_csv(): void
    {
        $admin = Pengguna::where('email', 'superadmin@dti.test')->firstOrFail();
        $this->actingAs($admin)->get('/superadmin/presensi/rekap?bulan=2026-11')->assertOk()->assertSee('Dimas Pratama')->assertSee('Ekspor CSV');

        $res = $this->get('/superadmin/presensi/rekap/csv?bulan=2026-11');
        $res->assertOk()->assertHeader('content-disposition', 'attachment; filename=rekap-kehadiran-2026-11.csv');
        $csv = $res->streamedContent();
        $this->assertStringContainsString('"Nama Peserta";Seksi;"Hari Hadir"', $csv);
        $this->assertStringContainsString('2026-11;"Dimas Pratama";"Pengembangan Sistem Informasi";5;1;1;1;1', $csv);
    }

    public function test_periode_tanpa_data(): void
    {
        $admin = Pengguna::where('email', 'superadmin@dti.test')->firstOrFail();
        $this->actingAs($admin)->get('/superadmin/presensi/rekap?bulan=2020-01')->assertOk()->assertSee('Data rekap tidak tersedia');
        $this->get('/superadmin/presensi/rekap/csv?bulan=2020-01')->assertSessionHasErrors('rekap');
    }
}
