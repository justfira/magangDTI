# Sistem Pengelolaan Magang DTI Universitas Andalas

Kerangka (struktur dasar) aplikasi web **Laravel 12** + **MySQL/MariaDB (phpMyAdmin)** untuk Sistem Pengelolaan Magang DTI,
disusun dari **Dokumen A3 Kelompok 12 PPSI** dan **ERD revisi setelah review dosen (20 tabel)**.

> Status: **kerangka siap dikembangkan.** Database, model, relasi, login, hak akses per peran, menu, dashboard,
> notifikasi, dan modul Program Magang (UC-02) **sudah berfungsi**. Modul lain sudah punya route, controller,
> pengecekan hak akses, dan daftar langkah dari use case A3 — tinggal diisi.

---

## 1. Kebutuhan

| Perangkat | Versi minimal | Keterangan |
|---|---|---|
| PHP | **8.2** | Sudah termasuk di XAMPP 8.2.x |
| MySQL / MariaDB | MySQL **8.0.16** / MariaDB **10.2** | XAMPP memakai MariaDB 10.4+ ✔ (dibutuhkan untuk CHECK constraint) |
| phpMyAdmin | bebas | Bawaan XAMPP, untuk membuat & melihat database |
| Composer | 2.x | https://getcomposer.org |

Ekstensi PHP yang harus aktif (di `php.ini` XAMPP): `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`.

## 2. Cara menjalankan (XAMPP)

1. Ekstrak ZIP ini, misalnya ke `C:\xampp\htdocs\magang-dti`.
2. Jalankan **Apache** dan **MySQL** dari XAMPP Control Panel.
3. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`) → **New** → nama database `magang_dti`, collation `utf8mb4_unicode_ci` → **Create**.
4. Buka terminal di folder proyek, lalu:

```bash
composer install
```

```bash
copy .env.example .env
```
(di Mac/Linux: `cp .env.example .env`)

```bash
php artisan key:generate
```

5. Isi tabel database — **pilih salah satu**:
   - **Cara A (disarankan):**
     ```bash
     php artisan migrate --seed
     ```
   - **Cara B (lewat phpMyAdmin):** pilih database `magang_dti` → tab **Import** → pilih `database/sql/magang_dti.sql` → **Go**.

6. Jalankan aplikasi:

```bash
php artisan serve
```

Buka `http://127.0.0.1:8000`.

> Jika user MySQL XAMPP Anda memakai password, ubah `DB_PASSWORD` di file `.env`.

## 3. Akun contoh

Semua password: **`password`**

| Email | Peran | Keterangan |
|---|---|---|
| superadmin@dti.test | Superadmin | |
| mentor@dti.test | Mentor DTI | Membimbing Dimas Pratama |
| kasi@dti.test | Kepala Seksi DTI | |
| direktur@dti.test | Direktur DTI | |
| peserta@mail.test | Peserta Magang | Dimas Pratama, sudah diterima & aktif |
| calon@mail.test | Calon Peserta | Rani Putri, pengajuan masih *Diajukan* |

Data contoh dibuat oleh `database/seeders/DemoSeeder.php` — hapus pemanggilannya di `DatabaseSeeder` sebelum dipakai DTI.

## 4. Peta use case → kode

| UC | Fitur | Controller (`app/Http/Controllers/…`) | Aktor | PJ | Status |
|---|---|---|---|---|---|
| UC-01 | Login & Logout | `Auth/LoginController` | Semua | Vania | ✅ Jadi |
| UC-02 | Mengelola Program Magang | `Superadmin/ProgramMagangController`, `Publik/InformasiProgramController` | Superadmin, Calon | Vania | ✅ Jadi |
| UC-03 | Mendaftar Rekrutmen DTI | `Pendaftaran/PendaftaranController` | Calon Peserta | Shafa | 🔧 Kerangka |
| UC-03 / UC-05 | Status & Perbaikan Pendaftaran | `Peserta/StatusPendaftaranController` | Calon Peserta | Shafa, Vania | 🔧 Kerangka |
| UC-06 | Informasi Penempatan | `Peserta/PenempatanController` | Peserta Magang | Ghina | 🔧 Kerangka |
| UC-07 | Tugas Magang (Peserta) | `Peserta/TugasController` | Peserta Magang | Shafa | 🔧 Kerangka |
| UC-08 | Presensi Reguler | `Peserta/PresensiController` | Peserta Magang | Zhahra | 🔧 Kerangka |
| UC-09 / UC-11 | Pengajuan Izin & Presensi Susulan | `Peserta/PengajuanKehadiranController` | Peserta Magang | Ghina, Zhahra | 🔧 Kerangka |
| UC-12 | Logbook Harian (Peserta) | `Peserta/LogbookController` | Peserta Magang | Fajri | 🔧 Kerangka |
| UC-15 | Sertifikat (Peserta) | `Peserta/SertifikatController` | Peserta Magang | Dinda | 🔧 Kerangka |
| UC-06 | Peserta Bimbingan | `Mentor/PesertaBimbinganController` | Mentor DTI | Ghina | 🔧 Kerangka |
| UC-07 | Tugas Magang (Mentor) | `Mentor/TugasController` | Mentor DTI | Shafa | 🔧 Kerangka |
| UC-09 / UC-11 | Verifikasi Izin & Presensi Susulan | `Mentor/VerifikasiKehadiranController` | Mentor DTI | Ghina, Zhahra | 🔧 Kerangka |
| UC-12 | Periksa Logbook | `Mentor/PeriksaLogbookController` | Mentor DTI | Fajri | 🔧 Kerangka |
| UC-13 | Evaluasi Peserta | `Mentor/EvaluasiController` | Mentor DTI | Fajri | 🔧 Kerangka |
| UC-04 | Permohonan Institusi | `Superadmin/PermohonanInstitusiController` | Superadmin | Vania | 🔧 Kerangka |
| UC-05 | Verifikasi Kelengkapan Pengajuan | `Superadmin/VerifikasiPengajuanController` | Superadmin | Vania | 🔧 Kerangka |
| UC-17 | Manajemen Akun | `Superadmin/AkunController` | Superadmin | Shafa | 🔧 Kerangka |
| UC-10 | Kelola Presensi | `Superadmin/KelolaPresensiController` | Superadmin | Zhahra | 🔧 Kerangka |
| UC-15 | Sertifikat (Superadmin) | `Superadmin/SertifikatController` | Superadmin | Dinda | 🔧 Kerangka |
| UC-05 | Rekomendasi Penerimaan | `KepalaSeksi/RekomendasiPenerimaanController` | Kepala Seksi DTI | Vania | 🔧 Kerangka |
| UC-06 | Penetapan Seksi & Mentor | `KepalaSeksi/PenempatanController` | Kepala Seksi DTI | Ghina | 🔧 Kerangka |
| UC-14 | Rekomendasi Penyelesaian Magang | `KepalaSeksi/PenyelesaianController` | Kepala Seksi DTI | Dinda | 🔧 Kerangka |
| UC-05 | Persetujuan Penerimaan | `Direktur/KeputusanPenerimaanController` | Direktur DTI | Vania | 🔧 Kerangka |
| UC-14 | Persetujuan Penyelesaian | `Direktur/KeputusanPenyelesaianController` | Direktur DTI | Dinda | 🔧 Kerangka |
| UC-16 | Monitoring Program Magang | `MonitoringController` | Superadmin, Kepala Seksi, Direktur | Dinda | 🔧 Kerangka |
| — | Lonceng notifikasi | `NotifikasiController` | Semua | — | ✅ Jadi |
| — | Dashboard per peran | `DashboardController` | Semua | — | ✅ Jadi |

Daftar seluruh route (84 route): `php artisan route:list --except-vendor`

## 5. Struktur folder penting

```
app/
├─ Http/
│  ├─ Controllers/
│  │  ├─ Auth/LoginController.php        UC-01 (jadi)
│  │  ├─ DashboardController.php         dashboard per peran (jadi)
│  │  ├─ NotifikasiController.php        lonceng notifikasi (jadi)
│  │  ├─ Publik/                         informasi program untuk calon (jadi)
│  │  ├─ Pendaftaran/                    UC-03 pendaftaran (tanpa login)
│  │  ├─ Superadmin/  KepalaSeksi/  Direktur/  Mentor/  Peserta/
│  │  └─ MonitoringController.php        UC-16
│  └─ Middleware/
│     ├─ CekPeran.php                    hak akses per peran -> route ->middleware('peran:MENTOR')
│     └─ PastikanAkunAktif.php           akun dinonaktifkan -> langsung logout
├─ Models/                               20 model = 20 tabel ERD, lengkap dengan relasi
├─ Services/GeofenceService.php          validasi radius & akurasi lokasi presensi
└─ Support/  Peran.php (kode peran)  Notifikasi.php (helper kirim notifikasi)
config/
├─ menu.php                              menu samping per peran (sesuai mockup A3)
└─ presensi.php                          titik & radius kantor DTI (dari .env)
database/
├─ migrations/                           20 tabel + 1 migrasi aturan CHECK
├─ seeders/                              peran, seksi, data contoh
└─ sql/magang_dti.sql                    alternatif impor lewat phpMyAdmin
resources/views/
├─ layouts/app.blade.php                 layout dengan menu, lonceng, logout
├─ rancangan.blade.php                   halaman sementara untuk fitur yang belum dikerjakan
└─ superadmin/program/                   contoh halaman lengkap (index + form)
routes/web.php                           semua route, dikelompokkan per peran
```

## 6. Keputusan desain (sesuai revisi ERD & review dosen)

| Hal | Implementasi |
|---|---|
| Sesi login & log aktivitas **tidak** disimpan di database | `SESSION_DRIVER=file`; login/logout & aksi penting ditulis ke `storage/logs/aktivitas-YYYY-MM-DD.log` (disimpan 90 hari) |
| Pelaku pemeriksaan/keputusan hanya pegawai DTI | Semua kolom pelaku menunjuk tabel `pegawai`; peserta tidak mungkin tercatat sebagai pemeriksa |
| Mentor hanya memproses peserta bimbingannya | `$this->pastikanBimbingan($peserta)` di `app/Http/Controllers/Controller.php` (berbasis `peserta_magang.id_mentor`) |
| Peserta hanya mengakses datanya sendiri | `$this->pastikanMilikSendiri($peserta)` |
| Peserta magang hanya lahir dari keputusan penerimaan | `peserta_magang.id_keputusan` wajib & unik |
| Satu progres tugas = satu review | Kolom review ada di `progres_tugas`; perbaikan = progres berikutnya |
| Kantor DTI hanya satu | Titik/radius di `.env` (`KANTOR_LAT`, `KANTOR_LONG`, `KANTOR_RADIUS_METER`, `KANTOR_BATAS_AKURASI_METER`) |
| Aturan bisnis dijaga database | Migrasi `..._tambah_aturan_check.php` (periode valid, status, catatan wajib saat Perlu Perbaikan, sertifikat hanya jika Disetujui, dll.) |
| Notifikasi | Lonceng di sistem (tabel `notifikasi`); buat lewat `App\Support\Notifikasi::kirim(...)` |

## 7. Cara mengerjakan satu modul

1. Buka controller modul Anda (lihat tabel di bagian 4). Setiap method punya komentar **Langkah** dari use case A3.
2. Tiru pola **`Superadmin/ProgramMagangController`**: `index` → `create`/`edit` (form) → validasi → simpan dalam `DB::transaction` → `redirect()->with('sukses', ...)`.
3. Ganti `return $this->halamanRancangan(...)` dengan `return view('nama.view', [...])`, lalu buat view-nya di `resources/views/` dengan `@extends('layouts.app')`.
4. Kirim notifikasi ke pihak terkait: `Notifikasi::kirim($idPengguna, 'JENIS', 'Pesan', 'nama_tabel', $id)`.
5. Catat aksi penting: `Log::channel('aktivitas')->info('aksi', [...])`.
6. Unggah berkas: `$request->file('berkas')->store('dokumen')` lalu simpan path-nya; jalankan sekali `php artisan storage:link`.

## 8. Pengujian

```bash
php artisan test
```

## 9. Catatan

- Nilai titik koordinat & radius kantor di `.env` masih **contoh** — tunggu konfirmasi DTI.
- Alur persetujuan Direktur (UC-05 langkah 9–11) masih usulan kelompok yang menunggu konfirmasi DTI.
- Sebelum dipakai sungguhan: ubah `APP_ENV=production`, `APP_DEBUG=false`, hapus data contoh, dan ganti semua password.
#   m a g a n g D T I  
 