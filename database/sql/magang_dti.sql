-- ============================================================
-- Database Sistem Pengelolaan Magang DTI Universitas Andalas
-- 20 tabel (ERD revisi setelah review dosen) + data contoh
-- Cara pakai di phpMyAdmin: buat database 'magang_dti' (utf8mb4_unicode_ci),
-- pilih database tsb -> tab Import -> pilih file ini -> Go.
-- Akun contoh (password: password): superadmin@dti.test, mentor@dti.test,
--   kasi@dti.test, direktur@dti.test, peserta@mail.test, calon@mail.test
-- Butuh MySQL 8.0.16+ / MariaDB 10.2+ (CHECK constraint).
-- ============================================================
/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `calon_peserta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calon_peserta` (
  `id_calon` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_pengajuan` int(10) unsigned NOT NULL COMMENT 'memuat',
  `nama_lengkap` varchar(150) NOT NULL,
  `email` varchar(191) DEFAULT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `nomor_induk_pendidikan` varchar(40) DEFAULT NULL,
  `institusi_asal` varchar(180) DEFAULT NULL,
  `jurusan_prodi` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id_calon`),
  KEY `calon_peserta_id_pengajuan_foreign` (`id_pengajuan`),
  CONSTRAINT `calon_peserta_id_pengajuan_foreign` FOREIGN KEY (`id_pengajuan`) REFERENCES `pengajuan` (`id_pengajuan`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `calon_peserta` DISABLE KEYS */;
INSERT INTO `calon_peserta` VALUES
(1,1,'Rani Putri','calon@mail.test',NULL,'2311521001','Universitas Andalas','Sistem Informasi'),
(2,2,'Dimas Pratama','peserta@mail.test',NULL,'2311522002','Universitas Andalas','Teknik Komputer');
/*!40000 ALTER TABLE `calon_peserta` ENABLE KEYS */;
DROP TABLE IF EXISTS `dokumen_pengajuan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dokumen_pengajuan` (
  `id_dokumen` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_pengajuan` int(10) unsigned NOT NULL COMMENT 'melampirkan',
  `id_syarat` int(10) unsigned DEFAULT NULL COMMENT 'memenuhi',
  `jenis_dokumen` varchar(80) NOT NULL,
  `nama_berkas` varchar(255) NOT NULL,
  `path_berkas` varchar(500) NOT NULL,
  `ukuran_bytes` bigint(20) unsigned DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `hasil_pemeriksaan` varchar(20) DEFAULT NULL,
  `catatan_pemeriksaan` text DEFAULT NULL,
  PRIMARY KEY (`id_dokumen`),
  KEY `dokumen_pengajuan_id_pengajuan_foreign` (`id_pengajuan`),
  KEY `dokumen_pengajuan_id_syarat_foreign` (`id_syarat`),
  CONSTRAINT `dokumen_pengajuan_id_pengajuan_foreign` FOREIGN KEY (`id_pengajuan`) REFERENCES `pengajuan` (`id_pengajuan`),
  CONSTRAINT `dokumen_pengajuan_id_syarat_foreign` FOREIGN KEY (`id_syarat`) REFERENCES `syarat_dokumen_program` (`id_syarat`),
  CONSTRAINT `chk_dokumen_hasil` CHECK (`hasil_pemeriksaan` is null or `hasil_pemeriksaan` in ('Sesuai','Tidak Sesuai')),
  CONSTRAINT `chk_dokumen_catatan` CHECK (`hasil_pemeriksaan` is null or `hasil_pemeriksaan` <> 'Tidak Sesuai' or `catatan_pemeriksaan` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `dokumen_pengajuan` DISABLE KEYS */;
/*!40000 ALTER TABLE `dokumen_pengajuan` ENABLE KEYS */;
DROP TABLE IF EXISTS `evaluasi_peserta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluasi_peserta` (
  `id_evaluasi` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_peserta_magang` int(10) unsigned NOT NULL COMMENT 'dievaluasi',
  `status_evaluasi` varchar(30) NOT NULL,
  `catatan` text DEFAULT NULL,
  `path_dokumen_evaluasi` varchar(500) DEFAULT NULL,
  `waktu_evaluasi` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_evaluasi`),
  UNIQUE KEY `evaluasi_peserta_id_peserta_magang_unique` (`id_peserta_magang`),
  CONSTRAINT `evaluasi_peserta_id_peserta_magang_foreign` FOREIGN KEY (`id_peserta_magang`) REFERENCES `peserta_magang` (`id_peserta_magang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `evaluasi_peserta` DISABLE KEYS */;
/*!40000 ALTER TABLE `evaluasi_peserta` ENABLE KEYS */;
DROP TABLE IF EXISTS `keputusan_penerimaan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `keputusan_penerimaan` (
  `id_keputusan` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_calon` int(10) unsigned NOT NULL COMMENT 'diputuskan',
  `id_kepala_seksi` int(10) unsigned NOT NULL COMMENT 'merekomendasikan',
  `id_direktur` int(10) unsigned DEFAULT NULL COMMENT 'memutuskan',
  `rekomendasi` varchar(20) NOT NULL,
  `catatan_rekomendasi` text DEFAULT NULL,
  `waktu_rekomendasi` timestamp NOT NULL DEFAULT current_timestamp(),
  `keputusan` varchar(20) DEFAULT NULL,
  `catatan_keputusan` text DEFAULT NULL,
  `waktu_keputusan` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_keputusan`),
  UNIQUE KEY `keputusan_penerimaan_id_calon_unique` (`id_calon`),
  KEY `keputusan_penerimaan_id_kepala_seksi_foreign` (`id_kepala_seksi`),
  KEY `keputusan_penerimaan_id_direktur_foreign` (`id_direktur`),
  CONSTRAINT `keputusan_penerimaan_id_calon_foreign` FOREIGN KEY (`id_calon`) REFERENCES `calon_peserta` (`id_calon`),
  CONSTRAINT `keputusan_penerimaan_id_direktur_foreign` FOREIGN KEY (`id_direktur`) REFERENCES `pegawai` (`id_pegawai`),
  CONSTRAINT `keputusan_penerimaan_id_kepala_seksi_foreign` FOREIGN KEY (`id_kepala_seksi`) REFERENCES `pegawai` (`id_pegawai`),
  CONSTRAINT `chk_rekomendasi` CHECK (`rekomendasi` in ('Terima','Tolak')),
  CONSTRAINT `chk_keputusan` CHECK (`keputusan` is null or `keputusan` in ('Diterima','Ditolak') and `id_direktur` is not null and `waktu_keputusan` is not null)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `keputusan_penerimaan` DISABLE KEYS */;
INSERT INTO `keputusan_penerimaan` VALUES
(1,2,3,4,'Terima',NULL,'2026-10-30 18:15:34','Diterima',NULL,'2026-10-31 18:15:34');
/*!40000 ALTER TABLE `keputusan_penerimaan` ENABLE KEYS */;
DROP TABLE IF EXISTS `koreksi_presensi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `koreksi_presensi` (
  `id_koreksi` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_presensi` int(10) unsigned NOT NULL COMMENT 'dikoreksi',
  `id_pengoreksi` int(10) unsigned NOT NULL COMMENT 'mengoreksi',
  `alasan` text NOT NULL,
  `path_bukti` varchar(500) NOT NULL,
  `data_sebelum` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data_sebelum`)),
  `data_sesudah` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data_sesudah`)),
  `waktu_koreksi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_koreksi`),
  KEY `koreksi_presensi_id_presensi_foreign` (`id_presensi`),
  KEY `koreksi_presensi_id_pengoreksi_foreign` (`id_pengoreksi`),
  CONSTRAINT `koreksi_presensi_id_pengoreksi_foreign` FOREIGN KEY (`id_pengoreksi`) REFERENCES `pegawai` (`id_pegawai`),
  CONSTRAINT `koreksi_presensi_id_presensi_foreign` FOREIGN KEY (`id_presensi`) REFERENCES `presensi` (`id_presensi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `koreksi_presensi` DISABLE KEYS */;
/*!40000 ALTER TABLE `koreksi_presensi` ENABLE KEYS */;
DROP TABLE IF EXISTS `logbook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logbook` (
  `id_logbook` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_peserta_magang` int(10) unsigned NOT NULL COMMENT 'mengisi',
  `tanggal_kegiatan` date NOT NULL,
  `uraian_aktivitas` text NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Dikirim',
  `catatan_mentor` text DEFAULT NULL,
  `waktu_kirim` timestamp NULL DEFAULT NULL,
  `waktu_periksa` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_logbook`),
  UNIQUE KEY `logbook_id_peserta_magang_tanggal_kegiatan_unique` (`id_peserta_magang`,`tanggal_kegiatan`),
  CONSTRAINT `logbook_id_peserta_magang_foreign` FOREIGN KEY (`id_peserta_magang`) REFERENCES `peserta_magang` (`id_peserta_magang`),
  CONSTRAINT `chk_logbook_status` CHECK (`status` in ('Dikirim','Disetujui','Perlu Perbaikan')),
  CONSTRAINT `chk_logbook_catatan` CHECK (`status` <> 'Perlu Perbaikan' or `catatan_mentor` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `logbook` DISABLE KEYS */;
/*!40000 ALTER TABLE `logbook` ENABLE KEYS */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'2026_11_03_000001_create_peran_table',1),
(2,'2026_11_03_000002_create_seksi_table',1),
(3,'2026_11_03_000003_create_pengguna_table',1),
(4,'2026_11_03_000004_create_pegawai_table',1),
(5,'2026_11_03_000005_create_notifikasi_table',1),
(6,'2026_11_03_000006_create_program_magang_table',1),
(7,'2026_11_03_000007_create_syarat_dokumen_program_table',1),
(8,'2026_11_03_000008_create_pengajuan_table',1),
(9,'2026_11_03_000009_create_dokumen_pengajuan_table',1),
(10,'2026_11_03_000010_create_calon_peserta_table',1),
(11,'2026_11_03_000011_create_keputusan_penerimaan_table',1),
(12,'2026_11_03_000012_create_peserta_magang_table',1),
(13,'2026_11_03_000013_create_tugas_table',1),
(14,'2026_11_03_000014_create_progres_tugas_table',1),
(15,'2026_11_03_000015_create_pengajuan_kehadiran_table',1),
(16,'2026_11_03_000016_create_presensi_table',1),
(17,'2026_11_03_000017_create_koreksi_presensi_table',1),
(18,'2026_11_03_000018_create_logbook_table',1),
(19,'2026_11_03_000019_create_evaluasi_peserta_table',1),
(20,'2026_11_03_000020_create_penyelesaian_magang_table',1),
(21,'2026_11_03_000021_tambah_aturan_check',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
DROP TABLE IF EXISTS `notifikasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifikasi` (
  `id_notifikasi` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_pengguna` int(10) unsigned NOT NULL COMMENT 'menerima',
  `jenis_entitas` varchar(80) DEFAULT NULL,
  `id_entitas` int(11) DEFAULT NULL,
  `jenis` varchar(80) NOT NULL,
  `pesan` text NOT NULL,
  `sudah_dibaca` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_notifikasi`),
  KEY `notifikasi_id_pengguna_sudah_dibaca_index` (`id_pengguna`,`sudah_dibaca`),
  CONSTRAINT `notifikasi_id_pengguna_foreign` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `notifikasi` DISABLE KEYS */;
INSERT INTO `notifikasi` VALUES
(1,6,'peserta_magang',1,'PENEMPATAN','Anda ditempatkan di seksi Pengembangan Sistem Informasi dengan mentor Budi Santoso.',0,'2026-11-03 18:15:34'),
(2,2,'tugas',1,'PROGRES_BARU','Dimas Pratama mengirim progres 1 tugas \"Fitur tambah & list data\".',0,'2026-11-03 18:15:34'),
(3,1,'pengajuan',1,'PENGAJUAN_BARU','Pengajuan baru DTI-2026-0001 dari Rani Putri.',0,'2026-11-03 18:15:34');
/*!40000 ALTER TABLE `notifikasi` ENABLE KEYS */;
DROP TABLE IF EXISTS `pegawai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pegawai` (
  `id_pegawai` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_pengguna` int(10) unsigned NOT NULL COMMENT 'adalah',
  `id_seksi` int(10) unsigned DEFAULT NULL COMMENT 'bertugas di',
  `nip` varchar(30) DEFAULT NULL,
  `jabatan` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id_pegawai`),
  UNIQUE KEY `pegawai_id_pengguna_unique` (`id_pengguna`),
  UNIQUE KEY `pegawai_nip_unique` (`nip`),
  KEY `pegawai_id_seksi_foreign` (`id_seksi`),
  CONSTRAINT `pegawai_id_pengguna_foreign` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`),
  CONSTRAINT `pegawai_id_seksi_foreign` FOREIGN KEY (`id_seksi`) REFERENCES `seksi` (`id_seksi`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `pegawai` DISABLE KEYS */;
INSERT INTO `pegawai` VALUES
(1,1,NULL,'199001012020011001',NULL),
(2,2,1,'199102022020011002',NULL),
(3,3,1,'198503032010012003',NULL),
(4,4,NULL,'197504042000031004',NULL);
/*!40000 ALTER TABLE `pegawai` ENABLE KEYS */;
DROP TABLE IF EXISTS `pengajuan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengajuan` (
  `id_pengajuan` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_program` int(10) unsigned DEFAULT NULL COMMENT 'menerima',
  `diajukan_oleh` int(10) unsigned DEFAULT NULL COMMENT 'mendaftar',
  `dicatat_oleh` int(10) unsigned DEFAULT NULL COMMENT 'mencatat',
  `id_pemeriksa` int(10) unsigned DEFAULT NULL COMMENT 'memeriksa',
  `kode_pengajuan` varchar(40) NOT NULL,
  `jalur` varchar(20) NOT NULL,
  `nama_institusi` varchar(180) DEFAULT NULL,
  `nomor_surat` varchar(100) DEFAULT NULL,
  `tanggal_surat` date DEFAULT NULL,
  `kontak_institusi` varchar(150) DEFAULT NULL,
  `status_pengajuan` varchar(35) NOT NULL DEFAULT 'Diajukan',
  `catatan_perbaikan` text DEFAULT NULL,
  `waktu_pemeriksaan` timestamp NULL DEFAULT NULL,
  `tanggal_pengajuan` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_pengajuan`),
  UNIQUE KEY `pengajuan_kode_pengajuan_unique` (`kode_pengajuan`),
  UNIQUE KEY `pengajuan_diajukan_oleh_id_program_unique` (`diajukan_oleh`,`id_program`),
  KEY `pengajuan_id_program_foreign` (`id_program`),
  KEY `pengajuan_dicatat_oleh_foreign` (`dicatat_oleh`),
  KEY `pengajuan_id_pemeriksa_foreign` (`id_pemeriksa`),
  CONSTRAINT `pengajuan_diajukan_oleh_foreign` FOREIGN KEY (`diajukan_oleh`) REFERENCES `pengguna` (`id_pengguna`),
  CONSTRAINT `pengajuan_dicatat_oleh_foreign` FOREIGN KEY (`dicatat_oleh`) REFERENCES `pegawai` (`id_pegawai`),
  CONSTRAINT `pengajuan_id_pemeriksa_foreign` FOREIGN KEY (`id_pemeriksa`) REFERENCES `pegawai` (`id_pegawai`),
  CONSTRAINT `pengajuan_id_program_foreign` FOREIGN KEY (`id_program`) REFERENCES `program_magang` (`id_program`),
  CONSTRAINT `chk_pengajuan_jalur` CHECK (`jalur` in ('DTI','INSTITUSI')),
  CONSTRAINT `chk_pengajuan_dti` CHECK (`jalur` <> 'DTI' or `id_program` is not null and `diajukan_oleh` is not null),
  CONSTRAINT `chk_pengajuan_institusi` CHECK (`jalur` <> 'INSTITUSI' or `dicatat_oleh` is not null and `nama_institusi` is not null and `nomor_surat` is not null),
  CONSTRAINT `chk_pengajuan_status` CHECK (`status_pengajuan` in ('Diajukan','Perlu Perbaikan','Lengkap','Selesai Diputuskan')),
  CONSTRAINT `chk_pengajuan_perbaikan` CHECK (`status_pengajuan` <> 'Perlu Perbaikan' or `catatan_perbaikan` is not null)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `pengajuan` DISABLE KEYS */;
INSERT INTO `pengajuan` VALUES
(1,1,5,NULL,NULL,'DTI-2026-0001','DTI',NULL,NULL,NULL,NULL,'Diajukan',NULL,NULL,'2026-11-03 18:15:34',NULL),
(2,1,6,NULL,1,'DTI-2026-0002','DTI',NULL,NULL,NULL,NULL,'Selesai Diputuskan',NULL,'2026-10-29 18:15:34','2026-10-28 18:15:34',NULL);
/*!40000 ALTER TABLE `pengajuan` ENABLE KEYS */;
DROP TABLE IF EXISTS `pengajuan_kehadiran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengajuan_kehadiran` (
  `id_pengajuan_kehadiran` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_peserta_magang` int(10) unsigned NOT NULL COMMENT 'mengajukan',
  `jenis` varchar(20) NOT NULL,
  `tanggal_kegiatan` date NOT NULL,
  `jenis_izin` varchar(20) DEFAULT NULL,
  `alasan` text NOT NULL,
  `keterangan` text DEFAULT NULL,
  `path_lampiran` varchar(500) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `akurasi_meter` decimal(8,2) DEFAULT NULL,
  `waktu_pengajuan` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(30) NOT NULL DEFAULT 'Menunggu Verifikasi',
  `catatan_keputusan` text DEFAULT NULL,
  `waktu_keputusan` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_pengajuan_kehadiran`),
  KEY `pengajuan_kehadiran_id_peserta_magang_foreign` (`id_peserta_magang`),
  CONSTRAINT `pengajuan_kehadiran_id_peserta_magang_foreign` FOREIGN KEY (`id_peserta_magang`) REFERENCES `peserta_magang` (`id_peserta_magang`),
  CONSTRAINT `chk_kehadiran_jenis` CHECK (`jenis` in ('IZIN','SUSULAN')),
  CONSTRAINT `chk_kehadiran_susulan_lokasi` CHECK (`jenis` <> 'SUSULAN' or `latitude` is not null and `longitude` is not null and `akurasi_meter` is not null),
  CONSTRAINT `chk_kehadiran_izin` CHECK (`jenis` <> 'IZIN' or `jenis_izin` in ('Penuh','Sebagian')),
  CONSTRAINT `chk_kehadiran_status` CHECK (`status` in ('Menunggu Verifikasi','Disetujui','Ditolak'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `pengajuan_kehadiran` DISABLE KEYS */;
/*!40000 ALTER TABLE `pengajuan_kehadiran` ENABLE KEYS */;
DROP TABLE IF EXISTS `pengguna`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengguna` (
  `id_pengguna` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_peran` int(10) unsigned NOT NULL COMMENT 'memiliki',
  `nama_lengkap` varchar(150) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status_akun` varchar(25) NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_pengguna`),
  UNIQUE KEY `pengguna_email_unique` (`email`),
  KEY `pengguna_id_peran_foreign` (`id_peran`),
  CONSTRAINT `pengguna_id_peran_foreign` FOREIGN KEY (`id_peran`) REFERENCES `peran` (`id_peran`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `pengguna` DISABLE KEYS */;
INSERT INTO `pengguna` VALUES
(1,1,'Admin DTI','superadmin@dti.test','$2y$12$DLEnV4pp6jnzSidyTRRai.v3mLXZH.r9IIT21jtBGadtrD930X/bO','Aktif','2026-11-03 18:15:33','2026-11-03 18:15:33'),
(2,2,'Budi Santoso','mentor@dti.test','$2y$12$XgxsloLn4dGU7ZnHuOv5JeEO.L5IvI9fKEZ8jrOquBcFuzXEnjaKm','Aktif','2026-11-03 18:15:33','2026-11-03 18:15:33'),
(3,3,'Siti Rahma','kasi@dti.test','$2y$12$d/4O9gT1DVOEGBpobb7FTe7RQOmTZOPXYE6vuFZqFu.D0ZqQrSZt.','Aktif','2026-11-03 18:15:33','2026-11-03 18:15:33'),
(4,4,'Dr. Andi Wijaya','direktur@dti.test','$2y$12$WxTuQrCu6w3JqnyqZbSNSu86ODc21DyW2U3QcozC0I8HFN/sLNvE.','Aktif','2026-11-03 18:15:33','2026-11-03 18:15:33'),
(5,5,'Rani Putri','calon@mail.test','$2y$12$9s7GO532eEqvOAJg5YUFXOLNmjDOZBToP1c39W1Q9TobzZZQfZyOO','Aktif','2026-11-03 18:15:34','2026-11-03 18:15:34'),
(6,5,'Dimas Pratama','peserta@mail.test','$2y$12$/S0yeTZKME3MYdPZZPCA0.8ekoxzWD4nC.UvqCwe8lR9bQbsotEO.','Aktif','2026-11-03 18:15:34','2026-11-03 18:15:34');
/*!40000 ALTER TABLE `pengguna` ENABLE KEYS */;
DROP TABLE IF EXISTS `penyelesaian_magang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `penyelesaian_magang` (
  `id_penyelesaian` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_peserta_magang` int(10) unsigned NOT NULL COMMENT 'diselesaikan',
  `id_kepala_seksi` int(10) unsigned NOT NULL COMMENT 'merekomendasikan',
  `id_direktur` int(10) unsigned DEFAULT NULL COMMENT 'memutuskan',
  `id_pengunggah` int(10) unsigned DEFAULT NULL COMMENT 'mengunggah',
  `rekomendasi` varchar(40) NOT NULL,
  `catatan_rekomendasi` text DEFAULT NULL,
  `waktu_rekomendasi` timestamp NOT NULL DEFAULT current_timestamp(),
  `keputusan` varchar(25) DEFAULT NULL,
  `catatan_keputusan` text DEFAULT NULL,
  `waktu_keputusan` timestamp NULL DEFAULT NULL,
  `nomor_sertifikat` varchar(100) DEFAULT NULL,
  `path_sertifikat` varchar(500) DEFAULT NULL,
  `waktu_unggah` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_penyelesaian`),
  UNIQUE KEY `penyelesaian_magang_nomor_sertifikat_unique` (`nomor_sertifikat`),
  KEY `penyelesaian_magang_id_peserta_magang_foreign` (`id_peserta_magang`),
  KEY `penyelesaian_magang_id_kepala_seksi_foreign` (`id_kepala_seksi`),
  KEY `penyelesaian_magang_id_direktur_foreign` (`id_direktur`),
  KEY `penyelesaian_magang_id_pengunggah_foreign` (`id_pengunggah`),
  CONSTRAINT `penyelesaian_magang_id_direktur_foreign` FOREIGN KEY (`id_direktur`) REFERENCES `pegawai` (`id_pegawai`),
  CONSTRAINT `penyelesaian_magang_id_kepala_seksi_foreign` FOREIGN KEY (`id_kepala_seksi`) REFERENCES `pegawai` (`id_pegawai`),
  CONSTRAINT `penyelesaian_magang_id_pengunggah_foreign` FOREIGN KEY (`id_pengunggah`) REFERENCES `pegawai` (`id_pegawai`),
  CONSTRAINT `penyelesaian_magang_id_peserta_magang_foreign` FOREIGN KEY (`id_peserta_magang`) REFERENCES `peserta_magang` (`id_peserta_magang`),
  CONSTRAINT `chk_penyelesaian_keputusan` CHECK (`keputusan` is null or `keputusan` in ('Disetujui','Ditolak') and `id_direktur` is not null),
  CONSTRAINT `chk_sertifikat_setelah_disetujui` CHECK (`path_sertifikat` is null or `keputusan` = 'Disetujui')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `penyelesaian_magang` DISABLE KEYS */;
/*!40000 ALTER TABLE `penyelesaian_magang` ENABLE KEYS */;
DROP TABLE IF EXISTS `peran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `peran` (
  `id_peran` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kode_peran` varchar(40) NOT NULL,
  `nama_peran` varchar(80) NOT NULL,
  PRIMARY KEY (`id_peran`),
  UNIQUE KEY `peran_kode_peran_unique` (`kode_peran`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `peran` DISABLE KEYS */;
INSERT INTO `peran` VALUES
(1,'SUPERADMIN','Superadmin'),
(2,'MENTOR','Mentor DTI'),
(3,'KEPALA_SEKSI','Kepala Seksi DTI'),
(4,'DIREKTUR','Direktur DTI'),
(5,'PESERTA','Calon/Peserta Magang');
/*!40000 ALTER TABLE `peran` ENABLE KEYS */;
DROP TABLE IF EXISTS `peserta_magang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `peserta_magang` (
  `id_peserta_magang` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_keputusan` int(10) unsigned NOT NULL COMMENT 'menjadi',
  `id_pengguna` int(10) unsigned NOT NULL COMMENT 'memakai akun',
  `id_seksi` int(10) unsigned DEFAULT NULL COMMENT 'ditempatkan',
  `id_mentor` int(10) unsigned DEFAULT NULL COMMENT 'membimbing',
  `ditetapkan_oleh` int(10) unsigned DEFAULT NULL COMMENT 'menetapkan',
  `tanggal_penempatan` date DEFAULT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `status_magang` varchar(30) NOT NULL DEFAULT 'Belum Mulai',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_peserta_magang`),
  UNIQUE KEY `peserta_magang_id_keputusan_unique` (`id_keputusan`),
  KEY `peserta_magang_id_pengguna_foreign` (`id_pengguna`),
  KEY `peserta_magang_id_seksi_foreign` (`id_seksi`),
  KEY `peserta_magang_id_mentor_foreign` (`id_mentor`),
  KEY `peserta_magang_ditetapkan_oleh_foreign` (`ditetapkan_oleh`),
  CONSTRAINT `peserta_magang_ditetapkan_oleh_foreign` FOREIGN KEY (`ditetapkan_oleh`) REFERENCES `pegawai` (`id_pegawai`),
  CONSTRAINT `peserta_magang_id_keputusan_foreign` FOREIGN KEY (`id_keputusan`) REFERENCES `keputusan_penerimaan` (`id_keputusan`),
  CONSTRAINT `peserta_magang_id_mentor_foreign` FOREIGN KEY (`id_mentor`) REFERENCES `pegawai` (`id_pegawai`),
  CONSTRAINT `peserta_magang_id_pengguna_foreign` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`),
  CONSTRAINT `peserta_magang_id_seksi_foreign` FOREIGN KEY (`id_seksi`) REFERENCES `seksi` (`id_seksi`),
  CONSTRAINT `chk_peserta_periode` CHECK (`tanggal_selesai` >= `tanggal_mulai`),
  CONSTRAINT `chk_peserta_status` CHECK (`status_magang` in ('Belum Mulai','Aktif','Selesai','Dihentikan'))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `peserta_magang` DISABLE KEYS */;
INSERT INTO `peserta_magang` VALUES
(1,1,6,1,2,3,'2026-11-01','2026-11-02','2027-02-03','Aktif','2026-11-03 18:15:34');
/*!40000 ALTER TABLE `peserta_magang` ENABLE KEYS */;
DROP TABLE IF EXISTS `presensi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `presensi` (
  `id_presensi` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_peserta_magang` int(10) unsigned NOT NULL COMMENT 'melakukan',
  `id_pengajuan_kehadiran` int(10) unsigned DEFAULT NULL COMMENT 'menghasilkan',
  `tanggal` date NOT NULL,
  `sumber` varchar(20) NOT NULL DEFAULT 'REGULER',
  `waktu_checkin` timestamp NULL DEFAULT NULL,
  `lat_checkin` decimal(10,7) DEFAULT NULL,
  `long_checkin` decimal(10,7) DEFAULT NULL,
  `akurasi_checkin` decimal(8,2) DEFAULT NULL,
  `waktu_checkout` timestamp NULL DEFAULT NULL,
  `lat_checkout` decimal(10,7) DEFAULT NULL,
  `long_checkout` decimal(10,7) DEFAULT NULL,
  `akurasi_checkout` decimal(8,2) DEFAULT NULL,
  PRIMARY KEY (`id_presensi`),
  UNIQUE KEY `presensi_id_peserta_magang_tanggal_unique` (`id_peserta_magang`,`tanggal`),
  UNIQUE KEY `presensi_id_pengajuan_kehadiran_unique` (`id_pengajuan_kehadiran`),
  CONSTRAINT `presensi_id_pengajuan_kehadiran_foreign` FOREIGN KEY (`id_pengajuan_kehadiran`) REFERENCES `pengajuan_kehadiran` (`id_pengajuan_kehadiran`),
  CONSTRAINT `presensi_id_peserta_magang_foreign` FOREIGN KEY (`id_peserta_magang`) REFERENCES `peserta_magang` (`id_peserta_magang`),
  CONSTRAINT `chk_presensi_sumber` CHECK (`sumber` in ('REGULER','SUSULAN','KOREKSI')),
  CONSTRAINT `chk_presensi_susulan` CHECK (`sumber` <> 'SUSULAN' or `id_pengajuan_kehadiran` is not null),
  CONSTRAINT `chk_presensi_checkout` CHECK (`waktu_checkout` is null or `waktu_checkout` >= `waktu_checkin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `presensi` DISABLE KEYS */;
/*!40000 ALTER TABLE `presensi` ENABLE KEYS */;
DROP TABLE IF EXISTS `program_magang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `program_magang` (
  `id_program` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dibuat_oleh` int(10) unsigned NOT NULL COMMENT 'membuat',
  `nama_program` varchar(150) NOT NULL,
  `tanggal_buka` date NOT NULL,
  `tanggal_tutup` date NOT NULL,
  `informasi` text DEFAULT NULL,
  `persyaratan` text DEFAULT NULL,
  `status_publikasi` varchar(30) NOT NULL DEFAULT 'Draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_program`),
  KEY `program_magang_dibuat_oleh_foreign` (`dibuat_oleh`),
  CONSTRAINT `program_magang_dibuat_oleh_foreign` FOREIGN KEY (`dibuat_oleh`) REFERENCES `pegawai` (`id_pegawai`),
  CONSTRAINT `chk_program_periode` CHECK (`tanggal_tutup` >= `tanggal_buka`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `program_magang` DISABLE KEYS */;
INSERT INTO `program_magang` VALUES
(1,1,'Magang DTI Periode 1 2026/2027','2026-10-27','2026-11-24','Program magang untuk mahasiswa di lingkungan Direktorat Teknologi Informasi Universitas Andalas.',NULL,'Dipublikasikan','2026-11-03 18:15:33','2026-11-03 18:15:33');
/*!40000 ALTER TABLE `program_magang` ENABLE KEYS */;
DROP TABLE IF EXISTS `progres_tugas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `progres_tugas` (
  `id_progres` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_tugas` int(10) unsigned NOT NULL COMMENT 'dilaporkan',
  `urutan` int(11) NOT NULL,
  `catatan_progres` text NOT NULL,
  `tautan_hasil` varchar(500) DEFAULT NULL,
  `path_berkas` varchar(500) DEFAULT NULL,
  `waktu_kirim` timestamp NOT NULL DEFAULT current_timestamp(),
  `hasil_review` varchar(30) DEFAULT NULL,
  `catatan_review` text DEFAULT NULL,
  `waktu_review` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_progres`),
  UNIQUE KEY `progres_tugas_id_tugas_urutan_unique` (`id_tugas`,`urutan`),
  CONSTRAINT `progres_tugas_id_tugas_foreign` FOREIGN KEY (`id_tugas`) REFERENCES `tugas` (`id_tugas`),
  CONSTRAINT `chk_review_hasil` CHECK (`hasil_review` is null or `hasil_review` in ('Perlu Perbaikan','Diterima')),
  CONSTRAINT `chk_review_catatan` CHECK (`hasil_review` is null or `hasil_review` <> 'Perlu Perbaikan' or `catatan_review` is not null)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `progres_tugas` DISABLE KEYS */;
INSERT INTO `progres_tugas` VALUES
(1,1,1,'Form tambah & list selesai',NULL,NULL,'2026-11-03 18:15:34',NULL,NULL,NULL);
/*!40000 ALTER TABLE `progres_tugas` ENABLE KEYS */;
DROP TABLE IF EXISTS `seksi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seksi` (
  `id_seksi` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nama_seksi` varchar(120) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  PRIMARY KEY (`id_seksi`),
  UNIQUE KEY `seksi_nama_seksi_unique` (`nama_seksi`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `seksi` DISABLE KEYS */;
INSERT INTO `seksi` VALUES
(1,'Pengembangan Sistem Informasi',NULL),
(2,'Infrastruktur dan Jaringan',NULL),
(3,'Layanan dan Dukungan TI',NULL);
/*!40000 ALTER TABLE `seksi` ENABLE KEYS */;
DROP TABLE IF EXISTS `syarat_dokumen_program`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `syarat_dokumen_program` (
  `id_syarat` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_program` int(10) unsigned NOT NULL COMMENT 'mensyaratkan',
  `nama_syarat` varchar(150) NOT NULL,
  `wajib` tinyint(1) NOT NULL DEFAULT 1,
  `urutan` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_syarat`),
  UNIQUE KEY `syarat_dokumen_program_id_program_urutan_unique` (`id_program`,`urutan`),
  CONSTRAINT `syarat_dokumen_program_id_program_foreign` FOREIGN KEY (`id_program`) REFERENCES `program_magang` (`id_program`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `syarat_dokumen_program` DISABLE KEYS */;
INSERT INTO `syarat_dokumen_program` VALUES
(1,1,'KTM',1,1),
(2,1,'CV',1,2),
(3,1,'Transkrip Nilai',1,3);
/*!40000 ALTER TABLE `syarat_dokumen_program` ENABLE KEYS */;
DROP TABLE IF EXISTS `tugas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tugas` (
  `id_tugas` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_peserta_magang` int(10) unsigned NOT NULL COMMENT 'menerima',
  `judul` varchar(180) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tenggat` date NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Ditugaskan',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_tugas`),
  KEY `tugas_id_peserta_magang_foreign` (`id_peserta_magang`),
  CONSTRAINT `tugas_id_peserta_magang_foreign` FOREIGN KEY (`id_peserta_magang`) REFERENCES `peserta_magang` (`id_peserta_magang`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tugas` DISABLE KEYS */;
INSERT INTO `tugas` VALUES
(1,1,'Fitur tambah & list data','Buat halaman tambah dan daftar data peserta.','2026-11-13','Diserahkan','2026-11-03 18:15:34');
/*!40000 ALTER TABLE `tugas` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

