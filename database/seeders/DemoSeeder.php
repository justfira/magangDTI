<?php

namespace Database\Seeders;

use App\Models\CalonPeserta;
use App\Models\KeputusanPenerimaan;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\PesertaMagang;
use App\Models\ProgramMagang;
use App\Models\Seksi;
use App\Support\Notifikasi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data contoh. Semua akun memakai password: password
 *  - superadmin@dti.test, mentor@dti.test, kasi@dti.test, direktur@dti.test
 *  - calon@mail.test   (pendaftar jalur DTI, status Diajukan)
 *  - peserta@mail.test (sudah diterima, peserta magang Aktif, dibimbing mentor@dti.test)
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $peran = Peran::pluck('id_peran', 'kode_peran');
        $seksi = Seksi::first();
        $akun = function (string $email, string $nama, string $kode) use ($peran) {
            return Pengguna::updateOrCreate(['email' => $email], [
                'id_peran' => $peran[$kode], 'nama_lengkap' => $nama,
                'password_hash' => Hash::make('password'), 'status_akun' => 'Aktif',
            ]);
        };
        $pegawai = fn (Pengguna $p, string $nip, ?int $idSeksi) => Pegawai::updateOrCreate(['id_pengguna' => $p->id_pengguna], ['nip' => $nip, 'id_seksi' => $idSeksi]);

        $superadmin = $pegawai($akun('superadmin@dti.test', 'Admin DTI', 'SUPERADMIN'), '199001012020011001', null);
        $mentor = $pegawai($akun('mentor@dti.test', 'Budi Santoso', 'MENTOR'), '199102022020011002', $seksi->id_seksi);
        $kasi = $pegawai($akun('kasi@dti.test', 'Siti Rahma', 'KEPALA_SEKSI'), '198503032010012003', $seksi->id_seksi);
        $direktur = $pegawai($akun('direktur@dti.test', 'Dr. Andi Wijaya', 'DIREKTUR'), '197504042000031004', null);

        $program = ProgramMagang::firstOrCreate(['nama_program' => 'Magang DTI Periode 1 2026/2027'], [
            'dibuat_oleh' => $superadmin->id_pegawai, 'tanggal_buka' => now()->subDays(7)->toDateString(),
            'tanggal_tutup' => now()->addDays(21)->toDateString(), 'status_publikasi' => 'Dipublikasikan',
            'informasi' => 'Program magang untuk mahasiswa di lingkungan Direktorat Teknologi Informasi Universitas Andalas.',
        ]);
        foreach (['KTM', 'CV', 'Transkrip Nilai'] as $i => $nama) {
            $program->syaratDokumenProgram()->firstOrCreate(['nama_syarat' => $nama], ['wajib' => true, 'urutan' => $i + 1]);
        }

        // Calon peserta: baru mendaftar
        $calon = $akun('calon@mail.test', 'Rani Putri', 'PESERTA');
        $pengajuanCalon = Pengajuan::firstOrCreate(['kode_pengajuan' => 'DTI-2026-0001'], [
            'jalur' => 'DTI', 'id_program' => $program->id_program, 'diajukan_oleh' => $calon->id_pengguna,
            'status_pengajuan' => 'Diajukan', 'tanggal_pengajuan' => now(),
        ]);
        CalonPeserta::firstOrCreate(['id_pengajuan' => $pengajuanCalon->id_pengajuan], [
            'nama_lengkap' => 'Rani Putri', 'email' => 'calon@mail.test', 'nomor_induk_pendidikan' => '2311521001',
            'institusi_asal' => 'Universitas Andalas', 'jurusan_prodi' => 'Sistem Informasi',
        ]);

        // Peserta magang aktif: alur lengkap pengajuan -> calon -> keputusan -> peserta_magang
        $peserta = $akun('peserta@mail.test', 'Dimas Pratama', 'PESERTA');
        $pengajuan = Pengajuan::firstOrCreate(['kode_pengajuan' => 'DTI-2026-0002'], [
            'jalur' => 'DTI', 'id_program' => $program->id_program, 'diajukan_oleh' => $peserta->id_pengguna,
            'status_pengajuan' => 'Selesai Diputuskan', 'id_pemeriksa' => $superadmin->id_pegawai,
            'waktu_pemeriksaan' => now()->subDays(5), 'tanggal_pengajuan' => now()->subDays(6),
        ]);
        $calonPeserta = CalonPeserta::firstOrCreate(['id_pengajuan' => $pengajuan->id_pengajuan], [
            'nama_lengkap' => 'Dimas Pratama', 'email' => 'peserta@mail.test', 'nomor_induk_pendidikan' => '2311522002',
            'institusi_asal' => 'Universitas Andalas', 'jurusan_prodi' => 'Teknik Komputer',
        ]);
        $keputusan = KeputusanPenerimaan::firstOrCreate(['id_calon' => $calonPeserta->id_calon], [
            'id_kepala_seksi' => $kasi->id_pegawai, 'rekomendasi' => 'Terima', 'waktu_rekomendasi' => now()->subDays(4),
            'id_direktur' => $direktur->id_pegawai, 'keputusan' => 'Diterima', 'waktu_keputusan' => now()->subDays(3),
        ]);
        $pm = PesertaMagang::firstOrCreate(['id_keputusan' => $keputusan->id_keputusan], [
            'id_pengguna' => $peserta->id_pengguna, 'id_seksi' => $seksi->id_seksi, 'id_mentor' => $mentor->id_pegawai,
            'ditetapkan_oleh' => $kasi->id_pegawai, 'tanggal_penempatan' => now()->subDays(2)->toDateString(),
            'tanggal_mulai' => now()->subDay()->toDateString(), 'tanggal_selesai' => now()->addMonths(3)->toDateString(),
            'status_magang' => 'Aktif',
        ]);
        $tugas = $pm->tugas()->firstOrCreate(['judul' => 'Fitur tambah & list data'], [
            'deskripsi' => 'Buat halaman tambah dan daftar data peserta.', 'tenggat' => now()->addDays(10)->toDateString(), 'status' => 'Diserahkan',
        ]);
        $tugas->progresTugas()->firstOrCreate(['urutan' => 1], ['catatan_progres' => 'Form tambah & list selesai', 'waktu_kirim' => now()]);

        if ($peserta->notifikasi()->doesntExist()) {
            Notifikasi::kirim($peserta->id_pengguna, 'PENEMPATAN', 'Anda ditempatkan di seksi '.$seksi->nama_seksi.' dengan mentor Budi Santoso.', 'peserta_magang', $pm->id_peserta_magang);
            Notifikasi::kirim($mentor->id_pengguna, 'PROGRES_BARU', 'Dimas Pratama mengirim progres 1 tugas "Fitur tambah & list data".', 'tugas', $tugas->id_tugas);
            Notifikasi::kirim($superadmin->id_pengguna, 'PENGAJUAN_BARU', 'Pengajuan baru DTI-2026-0001 dari Rani Putri.', 'pengajuan', $pengajuanCalon->id_pengajuan);
        }
    }
}
