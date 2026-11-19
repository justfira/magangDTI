<?php

namespace Database\Seeders;

use App\Models\CalonPeserta;
use App\Models\EvaluasiPeserta;
use App\Models\KeputusanPenerimaan;
use App\Models\Logbook;
use App\Models\Notifikasi as NotifikasiModel;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\PengajuanKehadiran;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\PesertaMagang;
use App\Models\Presensi;
use App\Models\ProgramMagang;
use App\Models\Seksi;
use App\Support\Notifikasi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Data skenario demo & uji Modul E — Logbook (UC-12) & Evaluasi Peserta (UC-13).
 * Idempoten dan selaras dengan struktur akun DemoSeeder (`password`: `password`).
 *
 * Skenario untuk mentor utama (`mentor@dti.test` / Budi Santoso):
 *  1. Dimas Pratama (`peserta@mail.test`)          — DTI, Aktif, Belum Dievaluasi (+ tugas, presensi, izin penuh, & logbook).
 *  2. Fajar Nugroho (`fajar.dti@mail.test`)        — DTI, Aktif, Belum Memenuhi (pembaruan evaluasi DTI).
 *  3. Citra Lestari (`citra.institusi@mail.test`)  — INSTITUSI, Aktif, Belum Dievaluasi (validasi unggah dokumen).
 *  4. Nadia Putri (`nadia.institusi@mail.test`)    — INSTITUSI, Aktif, Sudah Dinilai + PDF contoh (lihat & perbarui tanpa unggah ulang).
 *  5. Bima Saputra (`bima.selesai@mail.test`)      — INSTITUSI, Selesai, Sudah Dinilai + PDF contoh (mode baca-saja non-Aktif).
 *  6. Aulia Rahma (`aulia.mentor2@mail.test`)      — DTI, Aktif, bimbingan `mentor2@dti.test` (uji negatif 403 antar-mentor & antrean logbook mentor kedua).
 */
class ModulESeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PeranSeeder::class,
            SeksiSeeder::class,
        ]);

        $peran = Peran::pluck('id_peran', 'kode_peran');
        $kunci = Hash::make('password');

        $akun = fn (string $email, string $nama, string $kode) => Pengguna::firstOrCreate(
            ['email' => $email],
            ['id_peran' => $peran[$kode], 'nama_lengkap' => $nama, 'password_hash' => $kunci, 'status_akun' => 'Aktif'],
        );

        $seksiUtama = Seksi::firstOrCreate(['nama_seksi' => 'Pengembangan Sistem Informasi']);
        $pegawaiUtama = fn (Pengguna $p, string $nip, ?int $idSeksi) => Pegawai::firstOrCreate(
            ['id_pengguna' => $p->id_pengguna],
            ['nip' => $nip, 'id_seksi' => $idSeksi],
        );

        // Pegawai utama (selaras dengan DemoSeeder + 1 mentor kedua untuk uji kewenangan antar-mentor).
        $superadmin = $pegawaiUtama($akun('superadmin@dti.test', 'Admin DTI', 'SUPERADMIN'), '199001012020011001', null);
        $mentorUtama = $pegawaiUtama($akun('mentor@dti.test', 'Budi Santoso', 'MENTOR'), '199102022020011002', $seksiUtama->id_seksi);
        $mentorKedua = $pegawaiUtama($akun('mentor2@dti.test', 'Hendra Pratama', 'MENTOR'), '199205052021011005', $seksiUtama->id_seksi);
        $kasiUtama = $pegawaiUtama($akun('kasi@dti.test', 'Siti Rahma', 'KEPALA_SEKSI'), '198503032010012003', $seksiUtama->id_seksi);
        $direkturUtama = $pegawaiUtama($akun('direktur@dti.test', 'Dr. Andi Wijaya', 'DIREKTUR'), '197504042000031004', null);

        $programUtama = ProgramMagang::firstOrCreate(
            ['nama_program' => 'Magang DTI Periode 1 2026/2027'],
            [
                'dibuat_oleh' => $superadmin->id_pegawai,
                'tanggal_buka' => now()->subDays(14)->toDateString(),
                'tanggal_tutup' => now()->addDays(21)->toDateString(),
                'status_publikasi' => 'Dipublikasikan',
                'informasi' => 'Program magang untuk mahasiswa di lingkungan Direktorat Teknologi Informasi Universitas Andalas.',
            ],
        );

        $buatPeserta = function (array $cfg) use ($akun, $programUtama, $superadmin, $kasiUtama, $direkturUtama, $seksiUtama): PesertaMagang {
            $user = $akun($cfg['email'], $cfg['nama'], 'PESERTA');
            $jalur = $cfg['jalur'];

            $pengajuan = Pengajuan::firstOrCreate(
                ['kode_pengajuan' => $cfg['kode']],
                [
                    'jalur' => $jalur,
                    'id_program' => $jalur === 'DTI' ? $programUtama->id_program : null,
                    'diajukan_oleh' => $jalur === 'DTI' ? $user->id_pengguna : null,
                    'dicatat_oleh' => $jalur === 'INSTITUSI' ? $superadmin->id_pegawai : null,
                    'nama_institusi' => $jalur === 'INSTITUSI' ? $cfg['institusi'] : null,
                    'nomor_surat' => $jalur === 'INSTITUSI' ? $cfg['nomor_surat'] : null,
                    'tanggal_surat' => $jalur === 'INSTITUSI' ? now()->subDays(20)->toDateString() : null,
                    'status_pengajuan' => 'Selesai Diputuskan',
                    'id_pemeriksa' => $superadmin->id_pegawai,
                    'waktu_pemeriksaan' => now()->subDays(12),
                    'tanggal_pengajuan' => now()->subDays(14),
                ],
            );

            $calon = CalonPeserta::firstOrCreate(
                ['id_pengajuan' => $pengajuan->id_pengajuan],
                [
                    'nama_lengkap' => $cfg['nama'],
                    'email' => $cfg['email'],
                    'nomor_induk_pendidikan' => $cfg['nomor_induk'],
                    'institusi_asal' => $cfg['institusi'],
                    'jurusan_prodi' => $cfg['jurusan'],
                ],
            );

            $keputusan = KeputusanPenerimaan::firstOrCreate(
                ['id_calon' => $calon->id_calon],
                [
                    'id_kepala_seksi' => $kasiUtama->id_pegawai,
                    'rekomendasi' => 'Terima',
                    'waktu_rekomendasi' => now()->subDays(10),
                    'id_direktur' => $direkturUtama->id_pegawai,
                    'keputusan' => 'Diterima',
                    'waktu_keputusan' => now()->subDays(9),
                ],
            );

            return PesertaMagang::firstOrCreate(
                ['id_keputusan' => $keputusan->id_keputusan],
                [
                    'id_pengguna' => $user->id_pengguna,
                    'id_seksi' => $seksiUtama->id_seksi,
                    'id_mentor' => $cfg['mentor']->id_pegawai,
                    'ditetapkan_oleh' => $kasiUtama->id_pegawai,
                    'tanggal_penempatan' => now()->subDays(8)->toDateString(),
                    'tanggal_mulai' => $cfg['mulai'],
                    'tanggal_selesai' => $cfg['selesai'],
                    'status_magang' => $cfg['status_magang'],
                ],
            );
        };

        $tambahTugas = function (PesertaMagang $pm, array $daftarTugas): void {
            foreach ($daftarTugas as $t) {
                $pm->tugas()->firstOrCreate(
                    ['judul' => $t['judul']],
                    [
                        'deskripsi' => $t['deskripsi'],
                        'tenggat' => $t['tenggat'],
                        'status' => $t['status'],
                    ],
                );
            }
        };

        $kirimNotifEvaluasiJikaBelum = function (PesertaMagang $pm, EvaluasiPeserta $ev): void {
            if (! NotifikasiModel::where('id_pengguna', $pm->id_pengguna)->where('jenis', 'EVALUASI_PESERTA')->exists()) {
                Notifikasi::kirim(
                    $pm->id_pengguna,
                    'EVALUASI_PESERTA',
                    'Hasil evaluasi magang Anda telah dicatat oleh mentor dengan status '.$ev->status_evaluasi.'.',
                    'evaluasi_peserta',
                    $ev->id_evaluasi,
                );
            }
        };

        // Berkas PDF contoh valid pada disk `public` untuk Skenario 4 & 5 (jalur INSTITUSI).
        $pathPdfNadia = 'evaluasi-peserta/contoh-evaluasi-nadia.pdf';
        $pathPdfBima = 'evaluasi-peserta/contoh-evaluasi-bima.pdf';
        Storage::disk('public')->put($pathPdfNadia, $this->buatPdfContoh('Form Penilaian Magang - Nadia Putri (Politeknik Negeri Padang) - Sudah Dinilai'));
        Storage::disk('public')->put($pathPdfBima, $this->buatPdfContoh('Form Penilaian PKL - Bima Saputra (SMK Negeri 1 Padang) - Sudah Dinilai'));

        // Skenario 1: Dimas Pratama (DTI, Aktif, Belum Dievaluasi) + tugas, presensi, izin penuh, & variasi logbook.
        $pmDimas = $buatPeserta([
            'email' => 'peserta@mail.test',
            'nama' => 'Dimas Pratama',
            'kode' => 'DTI-2026-0002',
            'jalur' => 'DTI',
            'institusi' => 'Universitas Andalas',
            'jurusan' => 'Teknik Komputer',
            'nomor_induk' => '2311522002',
            'mentor' => $mentorUtama,
            'mulai' => now()->subDays(14)->toDateString(),
            'selesai' => now()->addMonths(3)->toDateString(),
            'status_magang' => 'Aktif',
        ]);
        if ($pmDimas->tanggal_mulai->gt(now()->subDays(10))) {
            $pmDimas->update(['tanggal_mulai' => now()->subDays(14)->toDateString()]);
        }
        $tambahTugas($pmDimas, [
            [
                'judul' => 'Fitur tambah & list data',
                'deskripsi' => 'Buat halaman tambah dan daftar data peserta.',
                'tenggat' => now()->addDays(10)->toDateString(),
                'status' => 'Diserahkan',
            ],
            [
                'judul' => 'Setup environment & struktur database',
                'deskripsi' => 'Menyiapkan instalasi Laravel, migrasi tabel, dan seeder awal.',
                'tenggat' => now()->subDays(2)->toDateString(),
                'status' => 'Selesai',
            ],
        ]);
        $this->isiPresensiDanLogbookContoh($pmDimas);

        // Skenario 2: Fajar Nugroho (DTI, Aktif, Belum Memenuhi -> pembaruan evaluasi DTI).
        $pmFajar = $buatPeserta([
            'email' => 'fajar.dti@mail.test',
            'nama' => 'Fajar Nugroho',
            'kode' => 'DTI-2026-0003',
            'jalur' => 'DTI',
            'institusi' => 'Universitas Andalas',
            'jurusan' => 'Sistem Informasi',
            'nomor_induk' => '2311521011',
            'mentor' => $mentorUtama,
            'mulai' => now()->subDays(20)->toDateString(),
            'selesai' => now()->addMonths(2)->toDateString(),
            'status_magang' => 'Aktif',
        ]);
        $tambahTugas($pmFajar, [
            [
                'judul' => 'Perancangan skema API internal',
                'deskripsi' => 'Menyusun spesifikasi endpoint REST dan payload JSON.',
                'tenggat' => now()->subDays(5)->toDateString(),
                'status' => 'Selesai',
            ],
            [
                'judul' => 'Dokumentasi endpoint & pengujian Postman',
                'deskripsi' => 'Lengkapi koleksi Postman serta contoh respons error.',
                'tenggat' => now()->addDays(3)->toDateString(),
                'status' => 'Perlu Perbaikan',
            ],
        ]);
        $evFajar = EvaluasiPeserta::updateOrCreate(
            ['id_peserta_magang' => $pmFajar->id_peserta_magang],
            [
                'status_evaluasi' => 'Belum Memenuhi',
                'catatan' => 'Tugas kedua masih perlu perbaikan dokumentasi API sebelum dinyatakan memenuhi.',
                'path_dokumen_evaluasi' => null,
                'waktu_evaluasi' => now()->subHours(5),
            ],
        );
        $kirimNotifEvaluasiJikaBelum($pmFajar, $evFajar);

        // Skenario 3: Citra Lestari (INSTITUSI, Aktif, Belum Dievaluasi -> validasi wajib unggah dokumen).
        $pmCitra = $buatPeserta([
            'email' => 'citra.institusi@mail.test',
            'nama' => 'Citra Lestari',
            'kode' => 'INST-2026-0001',
            'jalur' => 'INSTITUSI',
            'institusi' => 'SMK Negeri 2 Padang',
            'jurusan' => 'Rekayasa Perangkat Lunak',
            'nomor_induk' => '20261003',
            'nomor_surat' => '421.5/102/SMKN2-PDG/2026',
            'mentor' => $mentorUtama,
            'mulai' => now()->subDays(18)->toDateString(),
            'selesai' => now()->addMonths(2)->toDateString(),
            'status_magang' => 'Aktif',
        ]);
        $tambahTugas($pmCitra, [
            [
                'judul' => 'Pembuatan halaman profil layanan TI',
                'deskripsi' => 'Menyusun layout responsif untuk profil layanan DTI.',
                'tenggat' => now()->subDays(1)->toDateString(),
                'status' => 'Selesai',
            ],
        ]);

        // Skenario 4: Nadia Putri (INSTITUSI, Aktif, Sudah Dinilai + PDF contoh -> lihat & perbarui tanpa unggah ulang).
        $pmNadia = $buatPeserta([
            'email' => 'nadia.institusi@mail.test',
            'nama' => 'Nadia Putri',
            'kode' => 'INST-2026-0002',
            'jalur' => 'INSTITUSI',
            'institusi' => 'Politeknik Negeri Padang',
            'jurusan' => 'Teknologi Informasi',
            'nomor_induk' => '2301082004',
            'nomor_surat' => '118/PL9.TI/PKL/2026',
            'mentor' => $mentorUtama,
            'mulai' => now()->subDays(30)->toDateString(),
            'selesai' => now()->addDays(15)->toDateString(),
            'status_magang' => 'Aktif',
        ]);
        $tambahTugas($pmNadia, [
            [
                'judul' => 'Integrasi modul autentikasi pengguna',
                'deskripsi' => 'Menghubungkan middleware peran pada route aplikasi.',
                'tenggat' => now()->subDays(10)->toDateString(),
                'status' => 'Selesai',
            ],
            [
                'judul' => 'Pengujian fungsional halaman dashboard',
                'deskripsi' => 'Memastikan ringkasan metrik tampil sesuai peran login.',
                'tenggat' => now()->subDays(4)->toDateString(),
                'status' => 'Selesai',
            ],
        ]);
        $evNadia = EvaluasiPeserta::updateOrCreate(
            ['id_peserta_magang' => $pmNadia->id_peserta_magang],
            [
                'status_evaluasi' => 'Sudah Dinilai',
                'catatan' => 'Penilaian sesuai format form evaluasi Politeknik Negeri Padang.',
                'path_dokumen_evaluasi' => $pathPdfNadia,
                'waktu_evaluasi' => now()->subHours(3),
            ],
        );
        $kirimNotifEvaluasiJikaBelum($pmNadia, $evNadia);

        // Skenario 5: Bima Saputra (INSTITUSI, Selesai, Sudah Dinilai + PDF -> mode baca-saja non-Aktif).
        $pmBima = $buatPeserta([
            'email' => 'bima.selesai@mail.test',
            'nama' => 'Bima Saputra',
            'kode' => 'INST-2026-0003',
            'jalur' => 'INSTITUSI',
            'institusi' => 'SMK Negeri 1 Padang',
            'jurusan' => 'Teknik Komputer dan Jaringan',
            'nomor_induk' => '20261005',
            'nomor_surat' => '095/SMKN1-PDG/PKL/2026',
            'mentor' => $mentorUtama,
            'mulai' => now()->subMonths(3)->toDateString(),
            'selesai' => now()->subDays(2)->toDateString(),
            'status_magang' => 'Selesai',
        ]);
        $tambahTugas($pmBima, [
            [
                'judul' => 'Inventarisasi perangkat jaringan gedung A',
                'deskripsi' => 'Mendata switch, access point, dan jalur kabel lantai 1-3.',
                'tenggat' => now()->subDays(10)->toDateString(),
                'status' => 'Selesai',
            ],
        ]);
        EvaluasiPeserta::updateOrCreate(
            ['id_peserta_magang' => $pmBima->id_peserta_magang],
            [
                'status_evaluasi' => 'Sudah Dinilai',
                'catatan' => 'Seluruh rangkaian PKL telah diselesaikan dengan sangat baik.',
                'path_dokumen_evaluasi' => $pathPdfBima,
                'waktu_evaluasi' => now()->subDays(2),
            ],
        );

        // Skenario 6: Aulia Rahma (DTI, Aktif, bimbingan mentor2@dti.test -> uji negatif 403 & antrean logbook mentor kedua).
        $pmAulia = $buatPeserta([
            'email' => 'aulia.mentor2@mail.test',
            'nama' => 'Aulia Rahma',
            'kode' => 'DTI-2026-0004',
            'jalur' => 'DTI',
            'institusi' => 'Universitas Andalas',
            'jurusan' => 'Sistem Informasi',
            'nomor_induk' => '2311522019',
            'mentor' => $mentorKedua,
            'mulai' => now()->subDays(12)->toDateString(),
            'selesai' => now()->addMonths(2)->toDateString(),
            'status_magang' => 'Aktif',
        ]);
        $tambahTugas($pmAulia, [
            [
                'judul' => 'Audit topologi jaringan gedung rektorat',
                'deskripsi' => 'Pemetaan segmen VLAN dan dokumentasi rute utama.',
                'tenggat' => now()->addDays(7)->toDateString(),
                'status' => 'Ditugaskan',
            ],
        ]);
        $tanggalAulia = now()->subDays(4)->toDateString();
        if (! Logbook::where('id_peserta_magang', $pmAulia->id_peserta_magang)->whereDate('tanggal_kegiatan', $tanggalAulia)->exists()) {
            $pmAulia->logbook()->create([
                'tanggal_kegiatan' => $tanggalAulia,
                'uraian_aktivitas' => 'Konfigurasi jaringan lab dan dokumentasi topologi (skenario mentor kedua).',
                'status' => 'Dikirim',
                'waktu_kirim' => now()->subDays(4)->setTime(16, 30),
            ]);
        }
    }

    private function isiPresensiDanLogbookContoh(PesertaMagang $pm): void
    {
        foreach ([3, 2, 1] as $i) {
            $tanggal = now()->subDays($i)->toDateString();
            if (! Presensi::where('id_peserta_magang', $pm->id_peserta_magang)->whereDate('tanggal', $tanggal)->exists()) {
                $pm->presensi()->create([
                    'tanggal' => $tanggal,
                    'sumber' => 'REGULER',
                    'waktu_checkin' => now()->subDays($i)->setTime(8, 0),
                ]);
            }
        }

        if (! PengajuanKehadiran::where('id_peserta_magang', $pm->id_peserta_magang)
            ->where('jenis', 'IZIN')->whereDate('tanggal_kegiatan', now()->subDays(1)->toDateString())->exists()) {
            $pm->pengajuanKehadiran()->create([
                'jenis' => 'IZIN',
                'tanggal_kegiatan' => now()->subDays(1)->toDateString(),
                'jenis_izin' => 'Penuh',
                'alasan' => 'Keperluan keluarga (data uji)',
                'status' => 'Disetujui',
                'waktu_pengajuan' => now()->subDays(2),
                'waktu_keputusan' => now()->subDays(1),
            ]);
        }

        $variasi = [
            ['hari' => 5, 'status' => 'Dikirim', 'catatan' => null, 'uraian' => 'Menyusun spesifikasi fitur logbook dan merapikan tampilan daftar (data uji).'],
            ['hari' => 6, 'status' => 'Perlu Perbaikan', 'catatan' => 'Tambahkan detail hasil harian dan kendala yang dihadapi.', 'uraian' => 'Eksplorasi struktur tabel logbook dan skenario pemeriksaan (data uji).'],
            ['hari' => 7, 'status' => 'Disetujui', 'catatan' => 'Kegiatan sesuai rencana, dokumentasi rapi.', 'uraian' => 'Audit relasi mentor-peserta dan uji kewenangan pemeriksaan (data uji).'],
        ];
        foreach ($variasi as $v) {
            $tanggal = now()->subDays($v['hari'])->toDateString();
            if (! Logbook::where('id_peserta_magang', $pm->id_peserta_magang)->whereDate('tanggal_kegiatan', $tanggal)->exists()) {
                $pm->logbook()->create([
                    'tanggal_kegiatan' => $tanggal,
                    'uraian_aktivitas' => $v['uraian'],
                    'status' => $v['status'],
                    'catatan_mentor' => $v['catatan'],
                    'waktu_kirim' => now()->subDays($v['hari'])->setTime(17, 0),
                    'waktu_periksa' => $v['status'] === 'Dikirim' ? null : now()->subDays($v['hari'])->setTime(20, 0),
                ]);
            }
        }
    }

    /** Membuat berkas PDF 1.4 minimal yang valid agar dapat dibuka langsung di browser. */
    private function buatPdfContoh(string $teks): string
    {
        $aman = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $teks);
        $stream = "BT\n/F1 13 Tf\n50 750 Td\n({$aman}) Tj\nET\n";
        $objs = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n",
            "4 0 obj\n<< /Length ".strlen($stream)." >>\nstream\n{$stream}endstream\nendobj\n",
            "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objs as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj;
        }

        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objs) + 1)."\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objs); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size ".(count($objs) + 1)." /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF\n";

        return $pdf;
    }
}
