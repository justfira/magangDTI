<?php

namespace Tests\Feature;

use App\Models\CalonPeserta;
use App\Models\EvaluasiPeserta;
use App\Models\KeputusanPenerimaan;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvaluasiMentorTest extends TestCase
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
            'nama_program' => 'Program Uji UC-13',
            'tanggal_buka' => now()->subDays(10)->toDateString(),
            'tanggal_tutup' => now()->addDays(10)->toDateString(),
            'status_publikasi' => 'Dipublikasikan',
        ]);
    }

    private function buatPeserta(
        string $kode,
        string $jalur,
        Pegawai $mentor,
        string $statusMagang = 'Aktif',
        string $nama = 'Peserta Uji'
    ): PesertaMagang {
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
            'jalur' => $jalur,
            'id_program' => $jalur === 'DTI' ? $this->program->id_program : null,
            'diajukan_oleh' => $jalur === 'DTI' ? $user->id_pengguna : null,
            'dicatat_oleh' => $jalur === 'INSTITUSI' ? $this->superadmin->id_pegawai : null,
            'nama_institusi' => $jalur === 'INSTITUSI' ? 'SMK Negeri 1 Padang' : null,
            'nomor_surat' => $jalur === 'INSTITUSI' ? 'SURAT-'.$kode : null,
            'status_pengajuan' => 'Selesai Diputuskan',
            'tanggal_pengajuan' => now()->subDays(5),
        ]);

        $calon = CalonPeserta::create([
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'nama_lengkap' => $nama,
            'email' => $user->email,
            'nomor_induk_pendidikan' => '23115200'.$user->id_pengguna,
            'institusi_asal' => $jalur === 'DTI' ? 'Universitas Andalas' : 'SMK Negeri 1 Padang',
        ]);

        $keputusan = KeputusanPenerimaan::create([
            'id_calon' => $calon->id_calon,
            'id_kepala_seksi' => $this->kasi->id_pegawai,
            'rekomendasi' => 'Terima',
            'id_direktur' => $this->direktur->id_pegawai,
            'keputusan' => 'Diterima',
            'waktu_keputusan' => now()->subDays(3),
        ]);

        return PesertaMagang::create([
            'id_keputusan' => $keputusan->id_keputusan,
            'id_pengguna' => $user->id_pengguna,
            'id_seksi' => $this->seksi->id_seksi,
            'id_mentor' => $mentor->id_pegawai,
            'ditetapkan_oleh' => $this->kasi->id_pegawai,
            'tanggal_penempatan' => now()->subDays(2)->toDateString(),
            'tanggal_mulai' => now()->subDay()->toDateString(),
            'tanggal_selesai' => now()->addMonths(2)->toDateString(),
            'status_magang' => $statusMagang,
        ]);
    }

    public function test_mentor_melihat_daftar_peserta_bimbingan_sendiri_saja(): void
    {
        $pDti = $this->buatPeserta('DTI-001', 'DTI', $this->mentor1, 'Aktif', 'Budi DTI');
        $this->buatPeserta('INST-001', 'INSTITUSI', $this->mentor1, 'Aktif', 'Sari Institusi');
        $this->buatPeserta('DTI-002', 'DTI', $this->mentor2, 'Aktif', 'Peserta Mentor Lain');

        $pDti->tugas()->create([
            'judul' => 'Tugas Pertama',
            'deskripsi' => 'Deskripsi tugas',
            'tenggat' => now()->addDays(5)->toDateString(),
            'status' => 'Selesai',
        ]);

        $response = $this->actingAs($this->mentor1User)->get(route('mentor.evaluasi.index'));

        $response->assertOk()
            ->assertSee('Budi DTI')
            ->assertSee('Sari Institusi')
            ->assertSee('1/1 selesai')
            ->assertDontSee('Peserta Mentor Lain');
    }

    public function test_mentor_lain_ditolak_403_saat_mengakses_atau_menyimpan_evaluasi(): void
    {
        $peserta = $this->buatPeserta('DTI-403', 'DTI', $this->mentor1, 'Aktif', 'Milik Mentor Satu');

        $this->actingAs($this->mentor2User)
            ->get(route('mentor.evaluasi.edit', $peserta))
            ->assertForbidden();

        $this->actingAs($this->mentor2User)
            ->put(route('mentor.evaluasi.update', $peserta), [
                'status_evaluasi' => 'Memenuhi',
                'catatan' => 'Coba akses ilegal',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('evaluasi_peserta', 0);
    }

    public function test_peserta_bukan_aktif_tidak_dapat_dievaluasi(): void
    {
        $selesai = $this->buatPeserta('DTI-SLS', 'DTI', $this->mentor1, 'Selesai', 'Peserta Selesai');
        $belumMulai = $this->buatPeserta('DTI-BLM', 'DTI', $this->mentor1, 'Belum Mulai', 'Peserta Belum Mulai');

        foreach ([$selesai, $belumMulai] as $p) {
            $this->actingAs($this->mentor1User)
                ->get(route('mentor.evaluasi.edit', $p))
                ->assertRedirect(route('mentor.evaluasi.index'))
                ->assertSessionHas('info');

            $this->actingAs($this->mentor1User)
                ->put(route('mentor.evaluasi.update', $p), [
                    'status_evaluasi' => 'Memenuhi',
                    'catatan' => 'Tidak boleh tersimpan',
                ])
                ->assertRedirect(route('mentor.evaluasi.index'))
                ->assertSessionHas('info');
        }

        $this->assertDatabaseCount('evaluasi_peserta', 0);
    }

    public function test_evaluasi_jalur_dti_validasi_simpan_perbarui_dan_notifikasi(): void
    {
        $peserta = $this->buatPeserta('DTI-EVAL', 'DTI', $this->mentor1, 'Aktif', 'Andi DTI');
        $peserta->tugas()->create([
            'judul' => 'Implementasi Modul E',
            'deskripsi' => 'Selesaikan UC-13',
            'tenggat' => now()->addDays(7)->toDateString(),
            'status' => 'Selesai',
        ]);

        // Form edit tampil dengan tugas & opsi jalur DTI
        $this->actingAs($this->mentor1User)
            ->get(route('mentor.evaluasi.edit', $peserta))
            ->assertOk()
            ->assertSee('Implementasi Modul E')
            ->assertSee('Memenuhi')
            ->assertSee('Belum Memenuhi');

        // Gagal jika status dari jalur INSTITUSI dikirim ke peserta DTI
        $this->actingAs($this->mentor1User)
            ->put(route('mentor.evaluasi.update', $peserta), [
                'status_evaluasi' => 'Sudah Dinilai',
            ])
            ->assertSessionHasErrors('status_evaluasi');

        // Gagal jika Belum Memenuhi tanpa catatan
        $this->actingAs($this->mentor1User)
            ->put(route('mentor.evaluasi.update', $peserta), [
                'status_evaluasi' => 'Belum Memenuhi',
                'catatan' => '',
            ])
            ->assertSessionHasErrors('catatan');

        // Berhasil Belum Memenuhi dengan catatan
        $this->actingAs($this->mentor1User)
            ->put(route('mentor.evaluasi.update', $peserta), [
                'status_evaluasi' => 'Belum Memenuhi',
                'catatan' => 'Masih ada tugas revisi.',
            ])
            ->assertRedirect(route('mentor.evaluasi.index'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('evaluasi_peserta', [
            'id_peserta_magang' => $peserta->id_peserta_magang,
            'status_evaluasi' => 'Belum Memenuhi',
            'catatan' => 'Masih ada tugas revisi.',
            'path_dokumen_evaluasi' => null,
        ]);

        // Perbarui ke Memenuhi (updateOrCreate tetap 1 baris) tanpa catatan wajib
        $this->actingAs($this->mentor1User)
            ->put(route('mentor.evaluasi.update', $peserta), [
                'status_evaluasi' => 'Memenuhi',
                'catatan' => '',
            ])
            ->assertRedirect(route('mentor.evaluasi.index'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseCount('evaluasi_peserta', 1);
        $evaluasi = EvaluasiPeserta::where('id_peserta_magang', $peserta->id_peserta_magang)->firstOrFail();
        $this->assertSame('Memenuhi', $evaluasi->status_evaluasi);
        $this->assertNotNull($evaluasi->waktu_evaluasi);

        $notifs = NotifikasiModel::where('id_pengguna', $peserta->id_pengguna)
            ->where('jenis', 'EVALUASI_PESERTA')
            ->get();
        $this->assertCount(2, $notifs);
        $this->assertStringContainsString('Memenuhi', $notifs->last()->pesan);
    }

    public function test_evaluasi_jalur_institusi_wajib_berkas_saat_sudah_dinilai_dan_ganti_berkas(): void
    {
        Storage::fake('public');

        $peserta = $this->buatPeserta('INST-EVAL', 'INSTITUSI', $this->mentor1, 'Aktif', 'Citra SMK');

        // Form edit tampil dengan opsi jalur INSTITUSI
        $this->actingAs($this->mentor1User)
            ->get(route('mentor.evaluasi.edit', $peserta))
            ->assertOk()
            ->assertSee('Belum Dinilai')
            ->assertSee('Sudah Dinilai');

        // Gagal jika Sudah Dinilai tanpa dokumen
        $this->actingAs($this->mentor1User)
            ->put(route('mentor.evaluasi.update', $peserta), [
                'status_evaluasi' => 'Sudah Dinilai',
            ])
            ->assertSessionHasErrors('dokumen_evaluasi');

        // Berhasil jika Belum Dinilai tanpa dokumen
        $this->actingAs($this->mentor1User)
            ->put(route('mentor.evaluasi.update', $peserta), [
                'status_evaluasi' => 'Belum Dinilai',
                'catatan' => 'Menunggu format penilaian sekolah.',
            ])
            ->assertRedirect(route('mentor.evaluasi.index'))
            ->assertSessionHas('sukses');

        // Gagal jika format berkas tidak valid (mis. .exe)
        $this->actingAs($this->mentor1User)
            ->put(route('mentor.evaluasi.update', $peserta), [
                'status_evaluasi' => 'Sudah Dinilai',
                'dokumen_evaluasi' => UploadedFile::fake()->create('malware.exe', 50),
            ])
            ->assertSessionHasErrors('dokumen_evaluasi');

        // Berhasil jika Sudah Dinilai dengan PDF valid
        $file1 = UploadedFile::fake()->create('nilai.pdf', 100, 'application/pdf');
        $this->actingAs($this->mentor1User)
            ->put(route('mentor.evaluasi.update', $peserta), [
                'status_evaluasi' => 'Sudah Dinilai',
                'dokumen_evaluasi' => $file1,
                'catatan' => 'Penilaian selesai.',
            ])
            ->assertRedirect(route('mentor.evaluasi.index'))
            ->assertSessionHas('sukses');

        $evaluasi = EvaluasiPeserta::where('id_peserta_magang', $peserta->id_peserta_magang)->firstOrFail();
        $path1 = $evaluasi->path_dokumen_evaluasi;
        $this->assertNotNull($path1);
        Storage::disk('public')->assertExists($path1);

        // Berhasil update catatan saat Sudah Dinilai tanpa unggah ulang (berkas lama tetap dipertahankan)
        $this->actingAs($this->mentor1User)
            ->put(route('mentor.evaluasi.update', $peserta), [
                'status_evaluasi' => 'Sudah Dinilai',
                'catatan' => 'Catatan diperbarui.',
            ])
            ->assertRedirect(route('mentor.evaluasi.index'))
            ->assertSessionHas('sukses');

        $evaluasi->refresh();
        $this->assertSame($path1, $evaluasi->path_dokumen_evaluasi);
        Storage::disk('public')->assertExists($path1);

        // Ganti dokumen baru (DOCX valid) -> berkas lama dihapus, berkas baru tersimpan
        $file2 = UploadedFile::fake()->create('nilai-revisi.docx', 120, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->actingAs($this->mentor1User)
            ->put(route('mentor.evaluasi.update', $peserta), [
                'status_evaluasi' => 'Sudah Dinilai',
                'dokumen_evaluasi' => $file2,
                'catatan' => 'Dokumen revisi.',
            ])
            ->assertRedirect(route('mentor.evaluasi.index'))
            ->assertSessionHas('sukses');

        $evaluasi->refresh();
        $path2 = $evaluasi->path_dokumen_evaluasi;
        $this->assertNotSame($path1, $path2);
        Storage::disk('public')->assertMissing($path1);
        Storage::disk('public')->assertExists($path2);

        $notif = NotifikasiModel::where('id_pengguna', $peserta->id_pengguna)
            ->where('jenis', 'EVALUASI_PESERTA')
            ->latest('id_notifikasi')
            ->first();
        $this->assertNotNull($notif);
        $this->assertSame('evaluasi_peserta', $notif->jenis_entitas);
        $this->assertSame($evaluasi->id_evaluasi, $notif->id_entitas);
    }

    public function test_mentor_dapat_menyaring_daftar_evaluasi_berdasarkan_pencarian_jalur_dan_status(): void
    {
        $p1 = $this->buatPeserta('DTI-FLT-1', 'DTI', $this->mentor1, 'Aktif', 'Budi Santoso DTI');
        EvaluasiPeserta::create([
            'id_peserta_magang' => $p1->id_peserta_magang,
            'status_evaluasi' => 'Memenuhi',
            'waktu_evaluasi' => now()->subHour(),
        ]);

        $this->buatPeserta('INST-FLT-2', 'INSTITUSI', $this->mentor1, 'Aktif', 'Citra SMK');

        $p3 = $this->buatPeserta('INST-FLT-3', 'INSTITUSI', $this->mentor1, 'Aktif', 'Nadia Poltek');
        EvaluasiPeserta::create([
            'id_peserta_magang' => $p3->id_peserta_magang,
            'status_evaluasi' => 'Sudah Dinilai',
            'path_dokumen_evaluasi' => 'evaluasi-peserta/nadia.pdf',
            'waktu_evaluasi' => now(),
        ]);

        // Filter jalur INSTITUSI
        $this->actingAs($this->mentor1User)
            ->get(route('mentor.evaluasi.index', ['jalur' => 'INSTITUSI']))
            ->assertOk()
            ->assertSee('Citra SMK')
            ->assertSee('Nadia Poltek')
            ->assertDontSee('Budi Santoso DTI');

        // Filter status Belum Dievaluasi
        $this->actingAs($this->mentor1User)
            ->get(route('mentor.evaluasi.index', ['status' => 'Belum Dievaluasi']))
            ->assertOk()
            ->assertSee('Citra SMK')
            ->assertDontSee('Budi Santoso DTI')
            ->assertDontSee('Nadia Poltek');

        // Filter status Memenuhi
        $this->actingAs($this->mentor1User)
            ->get(route('mentor.evaluasi.index', ['status' => 'Memenuhi']))
            ->assertOk()
            ->assertSee('Budi Santoso DTI')
            ->assertDontSee('Citra SMK')
            ->assertDontSee('Nadia Poltek');

        // Filter pencarian nama
        $this->actingAs($this->mentor1User)
            ->get(route('mentor.evaluasi.index', ['cari' => 'Nadia']))
            ->assertOk()
            ->assertSee('Nadia Poltek')
            ->assertDontSee('Citra SMK')
            ->assertDontSee('Budi Santoso DTI');

        // Filter tanpa hasil tetap mempertahankan ringkasan total peserta
        $this->actingAs($this->mentor1User)
            ->get(route('mentor.evaluasi.index', ['cari' => 'TidakAdaNamaIni']))
            ->assertOk()
            ->assertSee('Tidak ada peserta yang cocok.')
            ->assertViewHas('ringkasan', fn (array $r) => ($r['total'] ?? null) === 3);
    }
}
