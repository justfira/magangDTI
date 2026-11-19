<?php

namespace Tests\Feature;

use App\Models\CalonPeserta;
use App\Models\KeputusanPenerimaan;
use App\Models\Logbook;
use App\Models\Notifikasi as NotifikasiModel;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\PesertaMagang;
use App\Models\ProgramMagang;
use App\Models\Seksi;
use Database\Seeders\PeranSeeder;
use Database\Seeders\SeksiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LogbookTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $mentor1User;
    private Pegawai $mentor1;
    private Pengguna $mentor2User;
    private Pegawai $mentor2;
    private Pegawai $superadmin;
    private Pegawai $kasi;
    private Pegawai $direktur;
    private Seksi $seksi;
    private ProgramMagang $program;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PeranSeeder::class);
        $this->seed(SeksiSeeder::class);

        $peran = Peran::pluck('id_peran', 'kode_peran');
        $this->seksi = Seksi::first();

        $buatPegawai = function (string $email, string $nama, string $kode, string $nip) use ($peran): array {
            $user = Pengguna::create([
                'id_peran' => $peran[$kode],
                'nama_lengkap' => $nama,
                'email' => $email,
                'password_hash' => Hash::make('password'),
                'status_akun' => 'Aktif',
            ]);
            $pegawai = Pegawai::create([
                'id_pengguna' => $user->id_pengguna,
                'id_seksi' => $this->seksi->id_seksi,
                'nip' => $nip,
            ]);

            return [$user, $pegawai];
        };

        [, $this->superadmin] = $buatPegawai('admin@test.local', 'Admin Uji', 'SUPERADMIN', 'NIP-ADM');
        [$this->mentor1User, $this->mentor1] = $buatPegawai('mentor1@test.local', 'Mentor Satu', 'MENTOR', 'NIP-M1');
        [$this->mentor2User, $this->mentor2] = $buatPegawai('mentor2@test.local', 'Mentor Dua', 'MENTOR', 'NIP-M2');
        [, $this->kasi] = $buatPegawai('kasi@test.local', 'Kasi Uji', 'KEPALA_SEKSI', 'NIP-K1');
        [, $this->direktur] = $buatPegawai('direktur@test.local', 'Direktur Uji', 'DIREKTUR', 'NIP-D1');

        $this->program = ProgramMagang::create([
            'dibuat_oleh' => $this->superadmin->id_pegawai,
            'nama_program' => 'Program Uji UC-12',
            'tanggal_buka' => now()->subDays(20)->toDateString(),
            'tanggal_tutup' => now()->addDays(20)->toDateString(),
            'status_publikasi' => 'Dipublikasikan',
        ]);
    }

    private function buatPeserta(
        string $kode,
        Pegawai $mentor,
        string $statusMagang = 'Aktif',
        string $nama = 'Peserta Logbook'
    ): array {
        $idPeranPeserta = Peran::where('kode_peran', 'PESERTA')->value('id_peran');
        $user = Pengguna::create([
            'id_peran' => $idPeranPeserta,
            'nama_lengkap' => $nama,
            'email' => strtolower($kode).'@mail.test',
            'password_hash' => Hash::make('password'),
            'status_akun' => 'Aktif',
        ]);

        $pengajuan = Pengajuan::create([
            'kode_pengajuan' => $kode,
            'jalur' => 'DTI',
            'id_program' => $this->program->id_program,
            'diajukan_oleh' => $user->id_pengguna,
            'status_pengajuan' => 'Selesai Diputuskan',
            'tanggal_pengajuan' => now()->subDays(15),
        ]);

        $calon = CalonPeserta::create([
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'nama_lengkap' => $nama,
            'email' => $user->email,
            'nomor_induk_pendidikan' => '23115300'.$user->id_pengguna,
            'institusi_asal' => 'Universitas Andalas',
        ]);

        $keputusan = KeputusanPenerimaan::create([
            'id_calon' => $calon->id_calon,
            'id_kepala_seksi' => $this->kasi->id_pegawai,
            'rekomendasi' => 'Terima',
            'id_direktur' => $this->direktur->id_pegawai,
            'keputusan' => 'Diterima',
            'waktu_keputusan' => now()->subDays(12),
        ]);

        $peserta = PesertaMagang::create([
            'id_keputusan' => $keputusan->id_keputusan,
            'id_pengguna' => $user->id_pengguna,
            'id_seksi' => $this->seksi->id_seksi,
            'id_mentor' => $mentor->id_pegawai,
            'ditetapkan_oleh' => $this->kasi->id_pegawai,
            'tanggal_penempatan' => now()->subDays(11)->toDateString(),
            'tanggal_mulai' => now()->subDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(20)->toDateString(),
            'status_magang' => $statusMagang,
        ]);

        return [$user, $peserta];
    }

    public function test_peserta_melihat_logbook_dan_penanda_hari_hadir_tanpa_logbook(): void
    {
        [$user, $peserta] = $this->buatPeserta('LB-IDX-01', $this->mentor1, 'Aktif', 'Peserta Hari Kosong');

        $hariTerisi = now()->subDays(3)->toDateString();
        $hariIzinPenuh = now()->subDays(2)->toDateString();
        $hariKosong = now()->subDays(1)->toDateString();

        foreach ([$hariTerisi, $hariIzinPenuh, $hariKosong] as $idx => $tgl) {
            $peserta->presensi()->create([
                'tanggal' => $tgl,
                'sumber' => 'REGULER',
                'waktu_checkin' => now()->subDays(3 - $idx)->setTime(8, 0),
            ]);
        }

        $peserta->logbook()->create([
            'tanggal_kegiatan' => $hariTerisi,
            'uraian_aktivitas' => 'Menyelesaikan modul presensi harian.',
            'status' => 'Dikirim',
            'waktu_kirim' => now()->subDays(3)->setTime(17, 0),
        ]);

        $peserta->pengajuanKehadiran()->create([
            'jenis' => 'IZIN',
            'tanggal_kegiatan' => $hariIzinPenuh,
            'jenis_izin' => 'Penuh',
            'alasan' => 'Izin penuh keluarga',
            'status' => 'Disetujui',
            'waktu_pengajuan' => now()->subDays(3),
            'waktu_keputusan' => now()->subDays(2),
        ]);

        $response = $this->actingAs($user)->get(route('peserta.logbook.index'));

        $response->assertOk()
            ->assertSee('Menyelesaikan modul presensi harian.')
            ->assertViewHas('hariKosong', function ($koleksi) use ($hariKosong, $hariTerisi, $hariIzinPenuh) {
                $tanggalList = $koleksi->map->tanggal->map->toDateString()->values()->all();

                return $tanggalList === [$hariKosong]
                    && ! in_array($hariTerisi, $tanggalList, true)
                    && ! in_array($hariIzinPenuh, $tanggalList, true);
            });
    }

    public function test_peserta_aktif_membuat_logbook_validasi_tanggal_duplikat_dan_notifikasi_mentor(): void
    {
        [$belumMulaiUser] = $this->buatPeserta('LB-BLM-01', $this->mentor1, 'Belum Mulai', 'Peserta Belum Mulai');
        $this->actingAs($belumMulaiUser)
            ->get(route('peserta.logbook.create'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('info');
        $this->actingAs($belumMulaiUser)
            ->post(route('peserta.logbook.store'), [
                'tanggal_kegiatan' => now()->toDateString(),
                'uraian_aktivitas' => 'Mencoba kirim sebelum aktif.',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('info');

        [$user, $peserta] = $this->buatPeserta('LB-CRT-01', $this->mentor1, 'Aktif', 'Peserta Aktif');

        // Gagal jika tanggal di luar periode magang atau uraian terlalu singkat
        $this->actingAs($user)
            ->post(route('peserta.logbook.store'), [
                'tanggal_kegiatan' => now()->subDays(25)->toDateString(),
                'uraian_aktivitas' => 'abc',
            ])
            ->assertSessionHasErrors(['tanggal_kegiatan', 'uraian_aktivitas']);

        // Berhasil membuat logbook baru
        $tanggalValid = now()->subDays(1)->toDateString();
        $this->actingAs($user)
            ->post(route('peserta.logbook.store'), [
                'tanggal_kegiatan' => $tanggalValid,
                'uraian_aktivitas' => 'Mengerjakan integrasi endpoint API peserta magang.',
            ])
            ->assertRedirect(route('peserta.logbook.index'))
            ->assertSessionHas('sukses');

        $logbook = Logbook::where('id_peserta_magang', $peserta->id_peserta_magang)->firstOrFail();
        $this->assertSame('Dikirim', $logbook->status);
        $this->assertSame($tanggalValid, $logbook->tanggal_kegiatan->toDateString());

        $notif = NotifikasiModel::where('id_pengguna', $this->mentor1User->id_pengguna)
            ->where('jenis', 'LOGBOOK_BARU')
            ->first();
        $this->assertNotNull($notif);
        $this->assertSame('logbook', $notif->jenis_entitas);
        $this->assertSame($logbook->id_logbook, $notif->id_entitas);

        // Gagal duplikasi pada tanggal yang sama
        $this->actingAs($user)
            ->post(route('peserta.logbook.store'), [
                'tanggal_kegiatan' => $tanggalValid,
                'uraian_aktivitas' => 'Mencoba membuat logbook ganda di tanggal sama.',
            ])
            ->assertSessionHasErrors('tanggal_kegiatan');

        $this->assertDatabaseCount('logbook', 1);
    }

    public function test_otorisasi_antar_peserta_dan_alur_revisi_logbook(): void
    {
        [$user1, $peserta1] = $this->buatPeserta('LB-REV-01', $this->mentor1, 'Aktif', 'Peserta Satu');
        [$user2] = $this->buatPeserta('LB-REV-02', $this->mentor1, 'Aktif', 'Peserta Dua');

        $logbookDikirim = $peserta1->logbook()->create([
            'tanggal_kegiatan' => now()->subDays(4)->toDateString(),
            'uraian_aktivitas' => 'Uraian awal masih menunggu pemeriksaan.',
            'status' => 'Dikirim',
            'waktu_kirim' => now()->subDays(4),
        ]);

        $logbookRevisi = $peserta1->logbook()->create([
            'tanggal_kegiatan' => now()->subDays(3)->toDateString(),
            'uraian_aktivitas' => 'Uraian awal yang kurang rinci.',
            'status' => 'Perlu Perbaikan',
            'catatan_mentor' => 'Jelaskan hasil pengujian unitnya.',
            'waktu_kirim' => now()->subDays(3),
            'waktu_periksa' => now()->subDays(2),
        ]);

        // Peserta lain ditolak 403
        $this->actingAs($user2)->get(route('peserta.logbook.show', $logbookRevisi))->assertForbidden();
        $this->actingAs($user2)->get(route('peserta.logbook.edit', $logbookRevisi))->assertForbidden();
        $this->actingAs($user2)->put(route('peserta.logbook.update', $logbookRevisi), [
            'uraian_aktivitas' => 'Akses tidak sah peserta lain.',
        ])->assertForbidden();

        // Logbook berstatus Dikirim tidak dapat diedit/direvisi
        $this->actingAs($user1)
            ->get(route('peserta.logbook.edit', $logbookDikirim))
            ->assertRedirect(route('peserta.logbook.index'))
            ->assertSessionHas('info');
        $this->actingAs($user1)
            ->put(route('peserta.logbook.update', $logbookDikirim), [
                'uraian_aktivitas' => 'Mencoba ubah padahal masih Dikirim.',
            ])
            ->assertRedirect(route('peserta.logbook.index'))
            ->assertSessionHas('info');

        // Logbook berstatus Perlu Perbaikan berhasil direvisi
        $this->actingAs($user1)->get(route('peserta.logbook.edit', $logbookRevisi))->assertOk();
        $this->actingAs($user1)
            ->put(route('peserta.logbook.update', $logbookRevisi), [
                'uraian_aktivitas' => 'Uraian diperbarui dengan hasil uji fitur dan tangkapan layar.',
            ])
            ->assertRedirect(route('peserta.logbook.index'))
            ->assertSessionHas('sukses');

        $logbookRevisi->refresh();
        $this->assertSame('Dikirim', $logbookRevisi->status);
        $this->assertNull($logbookRevisi->catatan_mentor);
        $this->assertNull($logbookRevisi->waktu_periksa);

        $notifRevisi = NotifikasiModel::where('id_pengguna', $this->mentor1User->id_pengguna)
            ->where('jenis', 'LOGBOOK_REVISI')
            ->first();
        $this->assertNotNull($notifRevisi);
        $this->assertSame($logbookRevisi->id_logbook, $notifRevisi->id_entitas);
    }

    public function test_mentor_memeriksa_logbook_bimbingan_validasi_dan_otorisasi(): void
    {
        [$pesertaUser1, $peserta1] = $this->buatPeserta('LB-MNT-01', $this->mentor1, 'Aktif', 'Budi Bimbingan M1');
        [, $peserta2] = $this->buatPeserta('LB-MNT-02', $this->mentor2, 'Aktif', 'Sari Bimbingan M2');

        $logbookM1 = $peserta1->logbook()->create([
            'tanggal_kegiatan' => now()->subDays(2)->toDateString(),
            'uraian_aktivitas' => 'Membuat halaman daftar logbook peserta.',
            'status' => 'Dikirim',
            'waktu_kirim' => now()->subDays(2),
        ]);
        $peserta2->logbook()->create([
            'tanggal_kegiatan' => now()->subDays(2)->toDateString(),
            'uraian_aktivitas' => 'Konfigurasi server mentor dua.',
            'status' => 'Dikirim',
            'waktu_kirim' => now()->subDays(2),
        ]);

        // Mentor 1 hanya melihat bimbingannya sendiri di index
        $this->actingAs($this->mentor1User)
            ->get(route('mentor.logbook.index'))
            ->assertOk()
            ->assertSee('Budi Bimbingan M1')
            ->assertDontSee('Sari Bimbingan M2');

        // Mentor 2 ditolak 403 saat melihat atau memeriksa logbook bimbingan Mentor 1
        $this->actingAs($this->mentor2User)
            ->get(route('mentor.logbook.show', $logbookM1))
            ->assertForbidden();
        $this->actingAs($this->mentor2User)
            ->post(route('mentor.logbook.periksa', $logbookM1), [
                'keputusan' => 'Disetujui',
            ])
            ->assertForbidden();

        // Gagal jika Perlu Perbaikan tanpa catatan_mentor
        $this->actingAs($this->mentor1User)
            ->post(route('mentor.logbook.periksa', $logbookM1), [
                'keputusan' => 'Perlu Perbaikan',
                'catatan_mentor' => '',
            ])
            ->assertSessionHasErrors('catatan_mentor');

        // Berhasil menandai Perlu Perbaikan + catatan
        $this->actingAs($this->mentor1User)
            ->post(route('mentor.logbook.periksa', $logbookM1), [
                'keputusan' => 'Perlu Perbaikan',
                'catatan_mentor' => 'Tambahkan rincian kendala teknis.',
            ])
            ->assertRedirect(route('mentor.logbook.index'))
            ->assertSessionHas('sukses');

        $logbookM1->refresh();
        $this->assertSame('Perlu Perbaikan', $logbookM1->status);
        $this->assertSame('Tambahkan rincian kendala teknis.', $logbookM1->catatan_mentor);
        $this->assertNotNull($logbookM1->waktu_periksa);

        // Tidak dapat diperiksa ulang bila status belum kembali Dikirim
        $this->actingAs($this->mentor1User)
            ->post(route('mentor.logbook.periksa', $logbookM1), [
                'keputusan' => 'Disetujui',
            ])
            ->assertRedirect(route('mentor.logbook.index'))
            ->assertSessionHas('info');

        // Peserta kirim revisi, lalu mentor menyetujui
        $this->actingAs($pesertaUser1)
            ->put(route('peserta.logbook.update', $logbookM1), [
                'uraian_aktivitas' => 'Membuat halaman daftar logbook dan mendokumentasikan kendala migrasi.',
            ])
            ->assertRedirect(route('peserta.logbook.index'));

        $this->actingAs($this->mentor1User)
            ->post(route('mentor.logbook.periksa', $logbookM1), [
                'keputusan' => 'Disetujui',
                'catatan_mentor' => 'Sudah lengkap dan rapi.',
            ])
            ->assertRedirect(route('mentor.logbook.index'))
            ->assertSessionHas('sukses');

        $logbookM1->refresh();
        $this->assertSame('Disetujui', $logbookM1->status);

        $notifsPeserta = NotifikasiModel::where('id_pengguna', $pesertaUser1->id_pengguna)
            ->where('jenis', 'LOGBOOK_DIPERIKSA')
            ->get();
        $this->assertCount(2, $notifsPeserta);
        $this->assertStringContainsString('Disetujui', $notifsPeserta->last()->pesan);
    }
}
