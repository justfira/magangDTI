<?php

namespace Tests\Feature;

use App\Models\CalonPeserta;
use App\Models\DokumenPengajuan;
use App\Models\KeputusanPenerimaan;
use App\Models\Notifikasi;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\ProgramMagang;
use Database\Seeders\PeranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Pengujian UC-05 Memverifikasi dan Memutuskan Penerimaan (FR-06 – FR-08). */
class VerifikasiPenerimaanTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $admin;
    private Pengguna $kasi;
    private Pengguna $direktur;
    private Pengguna $pendaftar;
    private ProgramMagang $program;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(PeranSeeder::class);

        $this->admin = $this->akun('SUPERADMIN', 'admin@dti.test', pegawai: true);
        $this->kasi = $this->akun('KEPALA_SEKSI', 'kasi@dti.test', pegawai: true);
        $this->direktur = $this->akun('DIREKTUR', 'direktur@dti.test', pegawai: true);
        $this->pendaftar = $this->akun('PESERTA', 'rani@mail.test');

        $this->program = ProgramMagang::create([
            'dibuat_oleh' => $this->admin->pegawai->id_pegawai, 'nama_program' => 'Magang DTI Periode 1',
            'tanggal_buka' => now()->subDays(5)->toDateString(), 'tanggal_tutup' => now()->addDays(20)->toDateString(),
            'status_publikasi' => 'Dipublikasikan',
        ]);
        foreach (['KTM', 'CV'] as $i => $nama) {
            $this->program->syaratDokumenProgram()->create(['nama_syarat' => $nama, 'wajib' => true, 'urutan' => $i + 1]);
        }
    }

    private function akun(string $kode, string $email, bool $pegawai = false): Pengguna
    {
        $p = Pengguna::create([
            'id_peran' => Peran::where('kode_peran', $kode)->value('id_peran'),
            'nama_lengkap' => 'Akun '.$kode, 'email' => $email,
            'password_hash' => Hash::make('password'), 'status_akun' => 'Aktif',
        ]);
        if ($pegawai) {
            Pegawai::create(['id_pengguna' => $p->id_pengguna]);
        }

        return $p->fresh();
    }

    /** Pengajuan jalur DTI dengan dokumen untuk syarat tertentu. */
    private function pengajuanDti(string $status = 'Diajukan', array $syarat = ['KTM', 'CV']): Pengajuan
    {
        // satu pendaftar hanya boleh satu pengajuan per program -> pengajuan berikutnya pakai akun baru
        $pendaftar = Pengajuan::where('diajukan_oleh', $this->pendaftar->id_pengguna)->exists()
            ? $this->akun('PESERTA', 'calon'.Pengajuan::count().'@mail.test')
            : $this->pendaftar;
        $p = Pengajuan::create([
            'kode_pengajuan' => 'DTI-2026-'.str_pad((string) (Pengajuan::count() + 1), 4, '0', STR_PAD_LEFT),
            'jalur' => 'DTI', 'id_program' => $this->program->id_program, 'diajukan_oleh' => $pendaftar->id_pengguna,
            'status_pengajuan' => $status, 'tanggal_pengajuan' => now(),
        ]);
        $p->calonPeserta()->create(['nama_lengkap' => 'Rani Putri', 'email' => 'rani@mail.test', 'institusi_asal' => 'Universitas Andalas']);
        foreach ($this->program->syaratDokumenProgram()->whereIn('nama_syarat', $syarat)->get() as $s) {
            $path = "pendaftaran/{$p->id_pengajuan}/{$s->nama_syarat}.pdf";
            Storage::disk('local')->put($path, '%PDF-1.4');
            $p->dokumenPengajuan()->create([
                'id_syarat' => $s->id_syarat, 'jenis_dokumen' => strtoupper($s->nama_syarat),
                'nama_berkas' => "{$s->nama_syarat}.pdf", 'path_berkas' => $path, 'ukuran_bytes' => 8,
            ]);
        }

        return $p;
    }

    private function pengajuanInstitusi(string $status = 'Lengkap', int $jumlahCalon = 2): Pengajuan
    {
        $p = Pengajuan::create([
            'kode_pengajuan' => 'INS-2026-'.str_pad((string) (Pengajuan::count() + 1), 4, '0', STR_PAD_LEFT),
            'jalur' => 'INSTITUSI', 'dicatat_oleh' => $this->admin->pegawai->id_pegawai,
            'nama_institusi' => 'Politeknik Negeri Padang', 'nomor_surat' => '12/PNP/2026',
            'status_pengajuan' => $status, 'tanggal_pengajuan' => now(),
        ]);
        for ($i = 1; $i <= $jumlahCalon; $i++) {
            $p->calonPeserta()->create(['nama_lengkap' => "Peserta Institusi {$i}", 'institusi_asal' => 'Politeknik Negeri Padang']);
        }

        return $p;
    }

    private function hasilSemua(Pengajuan $p, string $hasil = 'Sesuai'): array
    {
        return $p->dokumenPengajuan()->pluck('id_dokumen')
            ->mapWithKeys(fn ($id) => [$id => ['hasil' => $hasil, 'catatan' => $hasil === 'Tidak Sesuai' ? 'Buram' : '']])->all();
    }

    // ================================================================ Superadmin (FR-06)

    public function test_daftar_menampilkan_dua_jalur_dan_filter_status(): void
    {
        $this->pengajuanDti();
        $this->pengajuanInstitusi('Diajukan');

        $this->actingAs($this->admin)->get('/superadmin/verifikasi-pengajuan')->assertOk()
            ->assertSee('Rani Putri')->assertSee('Politeknik Negeri Padang')->assertSee('Rekrutmen DTI');

        $this->actingAs($this->admin)->get('/superadmin/verifikasi-pengajuan?status=Lengkap')->assertOk()
            ->assertSee('Data tidak tersedia.')->assertDontSee('Rani Putri');
    }

    public function test_detail_menampilkan_checklist_dan_tautan_lihat(): void
    {
        $p = $this->pengajuanDti(syarat: ['KTM']);
        $dok = $p->dokumenPengajuan()->first();

        $this->actingAs($this->admin)->get(route('superadmin.verifikasi.show', $p))->assertOk()
            ->assertSee('KTM.pdf')->assertSee('Belum diunggah oleh pendaftar')
            ->assertSee(route('pengajuan.dokumen', $dok), false);

        $this->actingAs($this->admin)->get(route('pengajuan.dokumen', $dok))->assertOk();
    }

    public function test_lengkap_menyimpan_pemeriksa_dan_memberitahu_kepala_seksi(): void
    {
        $p = $this->pengajuanDti();

        $this->actingAs($this->admin)->post(route('superadmin.verifikasi.periksa', $p), [
            'status' => 'Lengkap', 'dokumen' => $this->hasilSemua($p),
        ])->assertRedirect(route('superadmin.verifikasi.show', $p))->assertSessionHas('sukses');

        $p->refresh();
        $this->assertSame('Lengkap', $p->status_pengajuan);
        $this->assertSame($this->admin->pegawai->id_pegawai, $p->id_pemeriksa);
        $this->assertNotNull($p->waktu_pemeriksaan);
        $this->assertSame(2, DokumenPengajuan::where('hasil_pemeriksaan', 'Sesuai')->count());
        $this->assertTrue(Notifikasi::where('id_pengguna', $this->kasi->id_pengguna)->where('jenis', 'PENGAJUAN_LENGKAP')->exists());
    }

    public function test_a1_perlu_perbaikan_wajib_catatan_dan_terlihat_pendaftar(): void
    {
        $p = $this->pengajuanDti();

        $this->actingAs($this->admin)->post(route('superadmin.verifikasi.periksa', $p), ['status' => 'Perlu Perbaikan'])
            ->assertSessionHasErrors(['catatan_perbaikan' => 'Catatan perbaikan wajib diisi jika status Perlu Perbaikan.']);
        $this->assertSame('Diajukan', $p->fresh()->status_pengajuan);

        $this->actingAs($this->admin)->post(route('superadmin.verifikasi.periksa', $p), [
            'status' => 'Perlu Perbaikan', 'catatan_perbaikan' => 'KTM buram, unggah ulang.',
            'dokumen' => [$p->dokumenPengajuan()->first()->id_dokumen => ['hasil' => 'Tidak Sesuai', 'catatan' => 'Buram']],
        ])->assertSessionHasNoErrors();

        $this->assertSame('Perlu Perbaikan', $p->fresh()->status_pengajuan);
        $this->assertTrue(Notifikasi::where('id_pengguna', $this->pendaftar->id_pengguna)->where('jenis', 'PERLU_PERBAIKAN')->exists());

        $this->actingAs($this->pendaftar)->get('/peserta/pendaftaran')->assertOk()
            ->assertSee('KTM buram, unggah ulang.')->assertSee('Perbaiki Pengajuan');
    }

    public function test_lengkap_ditolak_jika_dokumen_wajib_belum_ada_atau_tidak_sesuai(): void
    {
        $p = $this->pengajuanDti(syarat: ['KTM']);   // CV belum diunggah
        $this->actingAs($this->admin)->post(route('superadmin.verifikasi.periksa', $p), [
            'status' => 'Lengkap', 'dokumen' => $this->hasilSemua($p),
        ])->assertSessionHasErrors('status');
        $this->assertSame('Diajukan', $p->fresh()->status_pengajuan);

        $q = $this->pengajuanDti();
        $this->actingAs($this->admin)->post(route('superadmin.verifikasi.periksa', $q), [
            'status' => 'Lengkap', 'dokumen' => $this->hasilSemua($q, 'Tidak Sesuai'),
        ])->assertSessionHasErrors('status');

        // Dokumen belum ditandai sama sekali
        $this->actingAs($this->admin)->post(route('superadmin.verifikasi.periksa', $q), ['status' => 'Lengkap'])
            ->assertSessionHasErrors('dokumen.'.$q->dokumenPengajuan()->first()->id_dokumen.'.hasil');
        $this->assertSame('Diajukan', $q->fresh()->status_pengajuan);
    }

    public function test_dokumen_tidak_sesuai_wajib_catatan(): void
    {
        $p = $this->pengajuanDti();
        $id = $p->dokumenPengajuan()->first()->id_dokumen;

        $this->actingAs($this->admin)->post(route('superadmin.verifikasi.periksa', $p), [
            'status' => 'Perlu Perbaikan', 'catatan_perbaikan' => 'Perbaiki.',
            'dokumen' => [$id => ['hasil' => 'Tidak Sesuai', 'catatan' => '']],
        ])->assertSessionHasErrors("dokumen.{$id}.catatan");
    }

    public function test_pengajuan_yang_sudah_lengkap_tidak_bisa_diperiksa_ulang(): void
    {
        $p = $this->pengajuanDti('Lengkap');
        $this->actingAs($this->admin)->post(route('superadmin.verifikasi.periksa', $p), [
            'status' => 'Perlu Perbaikan', 'catatan_perbaikan' => 'x',
        ])->assertSessionHas('gagal');
        $this->assertSame('Lengkap', $p->fresh()->status_pengajuan);
    }

    // ================================================================ Kepala Seksi (FR-07)

    public function test_kepala_seksi_hanya_melihat_calon_dari_pengajuan_lengkap(): void
    {
        $this->pengajuanDti('Diajukan');
        $this->pengajuanInstitusi('Lengkap', 1);

        $this->actingAs($this->kasi)->get('/kepala-seksi/rekomendasi-penerimaan')->assertOk()
            ->assertSee('Peserta Institusi 1')->assertDontSee('Rani Putri');
    }

    public function test_a2_rekomendasi_untuk_pengajuan_belum_lengkap_ditolak(): void
    {
        $calon = $this->pengajuanDti('Diajukan')->calonPeserta()->first();

        $this->actingAs($this->kasi)->post(route('kasi.rekomendasi.store', $calon), [
            'rekomendasi' => 'Terima', 'catatan_rekomendasi' => 'Bagus',
        ])->assertSessionHas('gagal', 'Pengajuan belum dinyatakan lengkap.');

        $this->assertSame(0, KeputusanPenerimaan::count());
    }

    public function test_kepala_seksi_merekomendasikan_dan_direktur_diberitahu(): void
    {
        $calon = $this->pengajuanDti('Lengkap')->calonPeserta()->first();

        $this->actingAs($this->kasi)->post(route('kasi.rekomendasi.store', $calon), ['rekomendasi' => 'Terima'])
            ->assertSessionHasErrors(['catatan_rekomendasi' => 'Catatan rekomendasi wajib diisi.']);

        $this->actingAs($this->kasi)->post(route('kasi.rekomendasi.store', $calon), [
            'rekomendasi' => 'Terima', 'catatan_rekomendasi' => 'Kompetensi sesuai.',
        ])->assertRedirect(route('kasi.rekomendasi.index'))->assertSessionHas('sukses');

        $k = KeputusanPenerimaan::firstOrFail();
        $this->assertSame('Terima', $k->rekomendasi);
        $this->assertSame($this->kasi->pegawai->id_pegawai, $k->id_kepala_seksi);
        $this->assertNull($k->keputusan);
        $this->assertTrue(Notifikasi::where('id_pengguna', $this->direktur->id_pengguna)->where('jenis', 'REKOMENDASI_BARU')->exists());

        // tidak bisa dua kali
        $this->actingAs($this->kasi)->post(route('kasi.rekomendasi.store', $calon), [
            'rekomendasi' => 'Tolak', 'catatan_rekomendasi' => 'Ubah',
        ])->assertSessionHas('gagal');
        $this->assertSame(1, KeputusanPenerimaan::count());
    }

    // ================================================================ Direktur (FR-08)

    public function test_direktur_menerima_dan_pendaftar_melihat_hasil(): void
    {
        $p = $this->pengajuanDti('Lengkap');
        $k = KeputusanPenerimaan::create([
            'id_calon' => $p->calonPeserta()->first()->id_calon, 'id_kepala_seksi' => $this->kasi->pegawai->id_pegawai,
            'rekomendasi' => 'Terima', 'waktu_rekomendasi' => now(),
        ]);

        $this->actingAs($this->direktur)->get('/direktur/keputusan-penerimaan?keputusan='.$k->id_keputusan)->assertOk()
            ->assertSee('menunggu konfirmasi DTI')->assertSee('Rani Putri');

        $this->actingAs($this->direktur)->put(route('direktur.penerimaan.update', $k), ['keputusan' => 'Diterima'])
            ->assertRedirect(route('direktur.penerimaan.index'))->assertSessionHas('sukses');

        $k->refresh();
        $this->assertSame('Diterima', $k->keputusan);
        $this->assertSame($this->direktur->pegawai->id_pegawai, $k->id_direktur);
        $this->assertNotNull($k->waktu_keputusan);
        $this->assertSame('Selesai Diputuskan', $p->fresh()->status_pengajuan);
        $this->assertTrue(Notifikasi::where('id_pengguna', $this->pendaftar->id_pengguna)->where('jenis', 'HASIL_PENERIMAAN')->exists());

        $this->actingAs($this->pendaftar)->get('/peserta/pendaftaran')->assertOk()->assertSee('Selamat, Anda diterima!');

        // tidak bisa diputuskan dua kali
        $this->actingAs($this->direktur)->put(route('direktur.penerimaan.update', $k), ['keputusan' => 'Ditolak'])
            ->assertSessionHas('gagal');
        $this->assertSame('Diterima', $k->fresh()->keputusan);
    }

    public function test_a3_institusi_selesai_setelah_semua_calon_diputuskan_dan_superadmin_diberitahu(): void
    {
        $p = $this->pengajuanInstitusi('Lengkap', 2);
        [$k1, $k2] = $p->calonPeserta->map(fn ($c) => KeputusanPenerimaan::create([
            'id_calon' => $c->id_calon, 'id_kepala_seksi' => $this->kasi->pegawai->id_pegawai,
            'rekomendasi' => 'Terima', 'waktu_rekomendasi' => now(),
        ]))->all();

        $this->actingAs($this->direktur)->put(route('direktur.penerimaan.update', $k1), ['keputusan' => 'Ditolak', 'catatan_keputusan' => 'Kuota penuh']);
        $this->assertSame('Lengkap', $p->fresh()->status_pengajuan);

        $this->actingAs($this->direktur)->put(route('direktur.penerimaan.update', $k2), ['keputusan' => 'Diterima']);
        $this->assertSame('Selesai Diputuskan', $p->fresh()->status_pengajuan);
        $this->assertSame(2, Notifikasi::where('id_pengguna', $this->admin->id_pengguna)->where('jenis', 'HASIL_PENERIMAAN')->count());

        // Langkah 12: Superadmin melihat hasil per calon
        $this->actingAs($this->admin)->get(route('superadmin.verifikasi.show', $p))->assertOk()
            ->assertSee('Ditolak')->assertSee('Diterima');
    }

    // ================================================================ E2 Akses ditolak

    public function test_e2_peran_lain_ditolak(): void
    {
        $p = $this->pengajuanDti();
        $calon = $p->calonPeserta()->first();
        $mentor = $this->akun('MENTOR', 'mentor@dti.test', pegawai: true);

        $this->actingAs($mentor)->post(route('superadmin.verifikasi.periksa', $p), ['status' => 'Lengkap'])->assertForbidden();
        $this->actingAs($this->kasi)->post(route('superadmin.verifikasi.periksa', $p), ['status' => 'Lengkap'])->assertForbidden();
        $this->actingAs($this->direktur)->post(route('kasi.rekomendasi.store', $calon), ['rekomendasi' => 'Terima', 'catatan_rekomendasi' => 'x'])->assertForbidden();
        $this->actingAs($this->pendaftar)->get(route('pengajuan.dokumen', $p->dokumenPengajuan()->first()))->assertForbidden();

        $this->assertSame('Diajukan', $p->fresh()->status_pengajuan);
        $this->assertSame(0, KeputusanPenerimaan::count());
    }
}
