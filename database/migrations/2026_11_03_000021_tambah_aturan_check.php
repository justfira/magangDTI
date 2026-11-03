<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Aturan bisnis yang dijaga langsung oleh database (CHECK constraint).
 * Butuh MySQL 8.0.16+ atau MariaDB 10.2+ (XAMPP terbaru sudah memenuhi).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            return;
        }
        foreach ([
            <<<'SQL'
ALTER TABLE `program_magang`
  ADD CONSTRAINT `chk_program_periode` CHECK (`tanggal_tutup` >= `tanggal_buka`)
SQL,
            <<<'SQL'
ALTER TABLE `pengajuan`
  ADD CONSTRAINT `chk_pengajuan_jalur` CHECK (`jalur` IN ('DTI','INSTITUSI')),
  ADD CONSTRAINT `chk_pengajuan_dti` CHECK (`jalur` <> 'DTI' OR (`id_program` IS NOT NULL AND `diajukan_oleh` IS NOT NULL)),
  ADD CONSTRAINT `chk_pengajuan_institusi` CHECK (`jalur` <> 'INSTITUSI' OR (`dicatat_oleh` IS NOT NULL AND `nama_institusi` IS NOT NULL AND `nomor_surat` IS NOT NULL)),
  ADD CONSTRAINT `chk_pengajuan_status` CHECK (`status_pengajuan` IN ('Diajukan','Perlu Perbaikan','Lengkap','Selesai Diputuskan')),
  ADD CONSTRAINT `chk_pengajuan_perbaikan` CHECK (`status_pengajuan` <> 'Perlu Perbaikan' OR `catatan_perbaikan` IS NOT NULL)
SQL,
            <<<'SQL'
ALTER TABLE `dokumen_pengajuan`
  ADD CONSTRAINT `chk_dokumen_hasil` CHECK (`hasil_pemeriksaan` IS NULL OR `hasil_pemeriksaan` IN ('Sesuai','Tidak Sesuai')),
  ADD CONSTRAINT `chk_dokumen_catatan` CHECK (`hasil_pemeriksaan` IS NULL OR `hasil_pemeriksaan` <> 'Tidak Sesuai' OR `catatan_pemeriksaan` IS NOT NULL)
SQL,
            <<<'SQL'
ALTER TABLE `keputusan_penerimaan`
  ADD CONSTRAINT `chk_rekomendasi` CHECK (`rekomendasi` IN ('Terima','Tolak')),
  ADD CONSTRAINT `chk_keputusan` CHECK (`keputusan` IS NULL OR (`keputusan` IN ('Diterima','Ditolak') AND `id_direktur` IS NOT NULL AND `waktu_keputusan` IS NOT NULL))
SQL,
            <<<'SQL'
ALTER TABLE `peserta_magang`
  ADD CONSTRAINT `chk_peserta_periode` CHECK (`tanggal_selesai` >= `tanggal_mulai`),
  ADD CONSTRAINT `chk_peserta_status` CHECK (`status_magang` IN ('Belum Mulai','Aktif','Selesai','Dihentikan'))
SQL,
            <<<'SQL'
ALTER TABLE `progres_tugas`
  ADD CONSTRAINT `chk_review_hasil` CHECK (`hasil_review` IS NULL OR `hasil_review` IN ('Perlu Perbaikan','Diterima')),
  ADD CONSTRAINT `chk_review_catatan` CHECK (`hasil_review` IS NULL OR `hasil_review` <> 'Perlu Perbaikan' OR `catatan_review` IS NOT NULL)
SQL,
            <<<'SQL'
ALTER TABLE `pengajuan_kehadiran`
  ADD CONSTRAINT `chk_kehadiran_jenis` CHECK (`jenis` IN ('IZIN','SUSULAN')),
  ADD CONSTRAINT `chk_kehadiran_susulan_lokasi` CHECK (`jenis` <> 'SUSULAN' OR (`latitude` IS NOT NULL AND `longitude` IS NOT NULL AND `akurasi_meter` IS NOT NULL)),
  ADD CONSTRAINT `chk_kehadiran_izin` CHECK (`jenis` <> 'IZIN' OR `jenis_izin` IN ('Penuh','Sebagian')),
  ADD CONSTRAINT `chk_kehadiran_status` CHECK (`status` IN ('Menunggu Verifikasi','Disetujui','Ditolak'))
SQL,
            <<<'SQL'
ALTER TABLE `presensi`
  ADD CONSTRAINT `chk_presensi_sumber` CHECK (`sumber` IN ('REGULER','SUSULAN','KOREKSI')),
  ADD CONSTRAINT `chk_presensi_susulan` CHECK (`sumber` <> 'SUSULAN' OR `id_pengajuan_kehadiran` IS NOT NULL),
  ADD CONSTRAINT `chk_presensi_checkout` CHECK (`waktu_checkout` IS NULL OR `waktu_checkout` >= `waktu_checkin`)
SQL,
            <<<'SQL'
ALTER TABLE `logbook`
  ADD CONSTRAINT `chk_logbook_status` CHECK (`status` IN ('Dikirim','Disetujui','Perlu Perbaikan')),
  ADD CONSTRAINT `chk_logbook_catatan` CHECK (`status` <> 'Perlu Perbaikan' OR `catatan_mentor` IS NOT NULL)
SQL,
            <<<'SQL'
ALTER TABLE `penyelesaian_magang`
  ADD CONSTRAINT `chk_penyelesaian_keputusan` CHECK (`keputusan` IS NULL OR (`keputusan` IN ('Disetujui','Ditolak') AND `id_direktur` IS NOT NULL)),
  ADD CONSTRAINT `chk_sertifikat_setelah_disetujui` CHECK (`path_sertifikat` IS NULL OR `keputusan` = 'Disetujui')
SQL
        ] as $sql) {
            DB::statement($sql);
        }
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            return;
        }
        DB::statement('ALTER TABLE `penyelesaian_magang` DROP CONSTRAINT `chk_sertifikat_setelah_disetujui`');
        DB::statement('ALTER TABLE `penyelesaian_magang` DROP CONSTRAINT `chk_penyelesaian_keputusan`');
        DB::statement('ALTER TABLE `logbook` DROP CONSTRAINT `chk_logbook_catatan`');
        DB::statement('ALTER TABLE `logbook` DROP CONSTRAINT `chk_logbook_status`');
        DB::statement('ALTER TABLE `presensi` DROP CONSTRAINT `chk_presensi_checkout`');
        DB::statement('ALTER TABLE `presensi` DROP CONSTRAINT `chk_presensi_susulan`');
        DB::statement('ALTER TABLE `presensi` DROP CONSTRAINT `chk_presensi_sumber`');
        DB::statement('ALTER TABLE `pengajuan_kehadiran` DROP CONSTRAINT `chk_kehadiran_status`');
        DB::statement('ALTER TABLE `pengajuan_kehadiran` DROP CONSTRAINT `chk_kehadiran_izin`');
        DB::statement('ALTER TABLE `pengajuan_kehadiran` DROP CONSTRAINT `chk_kehadiran_susulan_lokasi`');
        DB::statement('ALTER TABLE `pengajuan_kehadiran` DROP CONSTRAINT `chk_kehadiran_jenis`');
        DB::statement('ALTER TABLE `progres_tugas` DROP CONSTRAINT `chk_review_catatan`');
        DB::statement('ALTER TABLE `progres_tugas` DROP CONSTRAINT `chk_review_hasil`');
        DB::statement('ALTER TABLE `peserta_magang` DROP CONSTRAINT `chk_peserta_status`');
        DB::statement('ALTER TABLE `peserta_magang` DROP CONSTRAINT `chk_peserta_periode`');
        DB::statement('ALTER TABLE `keputusan_penerimaan` DROP CONSTRAINT `chk_keputusan`');
        DB::statement('ALTER TABLE `keputusan_penerimaan` DROP CONSTRAINT `chk_rekomendasi`');
        DB::statement('ALTER TABLE `dokumen_pengajuan` DROP CONSTRAINT `chk_dokumen_catatan`');
        DB::statement('ALTER TABLE `dokumen_pengajuan` DROP CONSTRAINT `chk_dokumen_hasil`');
        DB::statement('ALTER TABLE `pengajuan` DROP CONSTRAINT `chk_pengajuan_perbaikan`');
        DB::statement('ALTER TABLE `pengajuan` DROP CONSTRAINT `chk_pengajuan_status`');
        DB::statement('ALTER TABLE `pengajuan` DROP CONSTRAINT `chk_pengajuan_institusi`');
        DB::statement('ALTER TABLE `pengajuan` DROP CONSTRAINT `chk_pengajuan_dti`');
        DB::statement('ALTER TABLE `pengajuan` DROP CONSTRAINT `chk_pengajuan_jalur`');
        DB::statement('ALTER TABLE `program_magang` DROP CONSTRAINT `chk_program_periode`');
    }
};
