<?php

namespace Tests\Feature;

use App\Models\CalonPeserta;
use App\Models\DokumenPengajuan;
use App\Models\Notifikasi;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Pengguna;
use App\Models\Peran;
use Database\Seeders\PeranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Pengujian UC-04 Mencatat Permohonan Magang Institusi (FR-05): sisi Superadmin dan form online publik. */
class PermohonanInstitusiTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $admin;
    private Pegawai $pegawai;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(PeranSeeder::class);
        $this->admin = $this->akun('SUPERADMIN', 'admin@dti.test');
        $this->pegawai = Pegawai::create(['id_pengguna' => $this->admin->id_pengguna]);
    }

    private function akun(string $kode, string $email): Pengguna
    {
        return Pengguna::create([
            'id_peran' => Peran::where('kode_peran', $kode)->value('id_peran'),
            'nama_lengkap' => 'Akun '.$kode, 'email' => $email,
            'password_hash' => Hash::make('password'), 'status_akun' => 'Aktif',
        ]);
    }

    private function data(array $timpa = []): array
    {
        return array_merge([
            'nama_institusi' => 'Politeknik Negeri Padang',
            'nomor_surat' => '123/PL9/KM/2026',
            'tanggal_surat' => now()->subDays(2)->toDateString(),
            'kontak_nama' => 'Dr. Hendra',
            'kontak_email' => 'akademik@pnp.ac.id',
            'kontak_telepon' => '081234567890',
            'peserta' => [
                ['nama_lengkap' => 'Rani Putri', 'nomor_induk_pendidikan' => '2101001', 'jurusan_prodi' => 'Teknik Informatika', 'email' => 'rani@pnp.ac.id', 'no_hp' => ''],
                ['nama_lengkap' => 'Dimas Pratama', 'nomor_induk_pendidikan' => '2101002', 'jurusan_prodi' => 'Teknik Informatika', 'email' => '', 'no_hp' => '082233445566'],
                ['nama_lengkap' => '', 'nomor_induk_pendidikan' => '', 'jurusan_prodi' => '', 'email' => '', 'no_hp' => ''], // baris kosong diabaikan
            ],
            'surat' => UploadedFile::fake()->create('surat-pengajuan.pdf', 300, 'application/pdf'),
        ], $timpa);
    }

    // ------------------------------------------------------------ Superadmin

    public function test_superadmin_mencatat_permohonan_dengan_banyak_peserta(): void
    {
        $this->actingAs($this->admin)->post('/superadmin/permohonan-institusi', $this->data())
            ->assertRedirect(route('superadmin.permohonan.index'))
            ->assertSessionHas('sukses', fn ($pesan) => str_starts_with($pesan, 'Permohonan berhasil dicatat.'));

        $p = Pengajuan::firstOrFail();
        $this->assertSame('INSTITUSI', $p->jalur);
        $this->assertSame('Diajukan', $p->status_pengajuan);
        $this->assertSame($this->pegawai->id_pegawai, $p->dicatat_oleh);
        $this->assertSame('INS-'.now()->year.'-0001', $p->kode_pengajuan);
        $this->assertSame('Dr. Hendra · akademik@pnp.ac.id · 081234567890', $p->kontak_institusi);
        $this->assertSame(2, CalonPeserta::count());
        $this->assertSame('Politeknik Negeri Padang', CalonPeserta::first()->institusi_asal);

        $dok = DokumenPengajuan::firstOrFail();
        $this->assertSame('SURAT_PENGAJUAN', $dok->jenis_dokumen);
        Storage::disk('local')->assertExists($dok->path_berkas);
    }

    public function test_kode_permohonan_berurutan(): void
    {
        $this->actingAs($this->admin)->post('/superadmin/permohonan-institusi', $this->data());
        $this->actingAs($this->admin)->post('/superadmin/permohonan-institusi', $this->data(['nomor_surat' => '124/PL9/KM/2026']));

        $this->assertSame(['INS-'.now()->year.'-0001', 'INS-'.now()->year.'-0002'], Pengajuan::orderBy('id_pengajuan')->pluck('kode_pengajuan')->all());
    }

    public function test_a1_data_kosong_menampilkan_pesan_per_kolom_dan_tidak_menyimpan(): void
    {
        $this->actingAs($this->admin)->from('/superadmin/permohonan-institusi/create')
            ->post('/superadmin/permohonan-institusi', $this->data([
                'nama_institusi' => '', 'nomor_surat' => '', 'surat' => null,
                'peserta' => [['nama_lengkap' => 'Rani', 'nomor_induk_pendidikan' => '', 'jurusan_prodi' => '']],
            ]))
            ->assertRedirect('/superadmin/permohonan-institusi/create')
            ->assertSessionHas('gagal', 'Data belum lengkap atau berkas tidak sesuai.')
            ->assertSessionHasErrors([
                'nama_institusi' => 'Nama institusi wajib diisi.',
                'nomor_surat' => 'Nomor surat wajib diisi.',
                'surat' => 'Scan surat pengajuan wajib diunggah.',
                'peserta.0.nomor_induk_pendidikan' => 'NIM/NIS baris 1 wajib diisi.',
            ]);

        $this->assertSame(0, Pengajuan::count());
    }

    public function test_a1_berkas_salah_format_atau_terlalu_besar_ditolak(): void
    {
        $this->actingAs($this->admin)->post('/superadmin/permohonan-institusi', $this->data([
            'surat' => UploadedFile::fake()->create('surat.docx', 100),
        ]))->assertSessionHasErrors(['surat' => 'Format surat harus PDF, JPG, PNG.']);

        $this->actingAs($this->admin)->post('/superadmin/permohonan-institusi', $this->data([
            'surat' => UploadedFile::fake()->create('surat.pdf', 3000, 'application/pdf'),
        ]))->assertSessionHasErrors(['surat' => 'Ukuran surat maksimal 2 MB.']);

        $this->assertSame(0, Pengajuan::count());
    }

    public function test_tanpa_peserta_ditolak(): void
    {
        $this->actingAs($this->admin)->post('/superadmin/permohonan-institusi', $this->data([
            'peserta' => [['nama_lengkap' => '', 'nomor_induk_pendidikan' => '', 'jurusan_prodi' => '']],
        ]))->assertSessionHasErrors(['peserta' => 'Tambahkan minimal satu data peserta.']);
    }

    public function test_daftar_menampilkan_status_dan_nomor_surat_serta_bisa_dicari(): void
    {
        $this->actingAs($this->admin)->post('/superadmin/permohonan-institusi', $this->data());
        $this->actingAs($this->admin)->post('/superadmin/permohonan-institusi', $this->data([
            'nama_institusi' => 'SMK Negeri 2 Padang', 'nomor_surat' => '77/SMK2/2026',
        ]));

        $this->actingAs($this->admin)->get('/superadmin/permohonan-institusi')->assertOk()
            ->assertSee('Politeknik Negeri Padang')->assertSee('123/PL9/KM/2026')
            ->assertSee('SMK Negeri 2 Padang')->assertSee('Diajukan');

        $this->actingAs($this->admin)->get('/superadmin/permohonan-institusi?cari=77/SMK2')->assertOk()
            ->assertSee('SMK Negeri 2 Padang')->assertDontSee('Politeknik Negeri Padang');
    }

    public function test_detail_dan_surat_bisa_dibuka_superadmin(): void
    {
        $this->actingAs($this->admin)->post('/superadmin/permohonan-institusi', $this->data());
        $p = Pengajuan::firstOrFail();

        $this->actingAs($this->admin)->get(route('superadmin.permohonan.show', $p))->assertOk()
            ->assertSee('Rani Putri')->assertSee('Dimas Pratama')->assertSee('surat-pengajuan.pdf');
        $this->actingAs($this->admin)->get(route('superadmin.permohonan.surat', $p))->assertOk();
    }

    public function test_peran_lain_tidak_bisa_mengakses(): void
    {
        $mentor = $this->akun('MENTOR', 'mentor@dti.test');
        $this->actingAs($mentor)->get('/superadmin/permohonan-institusi')->assertForbidden();
        $this->actingAs($mentor)->post('/superadmin/permohonan-institusi', $this->data())->assertForbidden();
        $this->assertSame(0, Pengajuan::count());
    }

    // ------------------------------------------------------------ Publik (form online institusi)

    public function test_landing_institusi_bisa_dibuka_tanpa_login(): void
    {
        $this->get('/permohonan-institusi')->assertOk()
            ->assertSee('Formulir Permohonan Magang Institusi')
            ->assertSee('Kirim Permohonan');
    }

    public function test_institusi_mengirim_form_online(): void
    {
        $this->post('/permohonan-institusi', $this->data(['pernyataan' => '1']))
            ->assertRedirect(route('publik.permohonan.terkirim'));

        $p = Pengajuan::firstOrFail();
        $this->assertNull($p->dicatat_oleh);
        $this->assertSame('Diajukan', $p->status_pengajuan);
        $this->assertSame(2, $p->calonPeserta()->count());

        // Superadmin mendapat notifikasi lonceng
        $this->assertTrue(Notifikasi::where('id_pengguna', $this->admin->id_pengguna)->where('jenis', 'PERMOHONAN_INSTITUSI')->exists());

        $this->get(route('publik.permohonan.terkirim'))->assertOk()->assertSee($p->kode_pengajuan);

        // tampil di daftar Superadmin dengan sumber "Form online"
        $this->actingAs($this->admin)->get('/superadmin/permohonan-institusi?sumber=online')->assertOk()
            ->assertSee('Politeknik Negeri Padang')->assertSee('Form online');
    }

    public function test_form_online_wajib_centang_pernyataan(): void
    {
        $this->post('/permohonan-institusi', $this->data())
            ->assertSessionHasErrors(['pernyataan' => 'Centang pernyataan bahwa data yang diisi sudah benar.']);
        $this->assertSame(0, Pengajuan::count());
    }

    public function test_honeypot_terisi_tidak_disimpan(): void
    {
        $this->post('/permohonan-institusi', $this->data(['pernyataan' => '1', 'situs_web' => 'http://spam.test']))
            ->assertRedirect(route('publik.permohonan.create'));
        $this->assertSame(0, Pengajuan::count());
    }

    public function test_halaman_terkirim_tanpa_sesi_dialihkan_ke_form(): void
    {
        $this->get('/permohonan-institusi/terkirim')->assertRedirect(route('publik.permohonan.create'));
    }
}
