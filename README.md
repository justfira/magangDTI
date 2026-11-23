# Magang DTI Management Project

**Sistem Informasi Manajemen Magang DTI — Kelompok 12**

## 1. Deskripsi Proyek

Magang DTI Management Project merupakan aplikasi berbasis web yang dikembangkan untuk mendukung pengelolaan dan pemantauan kegiatan magang di lingkungan DTI. Sistem ini dirancang untuk mengintegrasikan proses administrasi, pendaftaran, penerimaan peserta, pengelolaan aktivitas magang, presensi, evaluasi, hingga penyelesaian program magang dalam satu platform.

Pengembangan sistem dilakukan sebagai bagian dari mata kuliah Pengembangan dan Pengelolaan Sistem Informasi (PPSI) oleh Kelompok 12.

## 2. Latar Belakang dan Tujuan

### Latar Belakang

Pengelolaan kegiatan magang melibatkan berbagai proses dan pihak, mulai dari calon peserta, peserta magang, mentor, hingga pihak pengelola. Apabila proses tersebut tidak terintegrasi, pengelolaan data peserta, administrasi pendaftaran, presensi, tugas, dan evaluasi berpotensi mengalami kendala dalam pencatatan, pemantauan, serta penyampaian informasi.

Oleh karena itu, diperlukan sistem informasi yang dapat membantu mengintegrasikan proses pengelolaan magang agar lebih terstruktur dan mudah dipantau.

### Tujuan

Pengembangan sistem ini bertujuan untuk:

1. Mengintegrasikan proses administrasi dan pengelolaan kegiatan magang.
2. Mempermudah proses pendaftaran dan penerimaan peserta magang.
3. Mendukung pengelolaan presensi, izin, tugas, dan logbook peserta.
4. Memfasilitasi pemantauan perkembangan serta evaluasi peserta magang.
5. Menyediakan informasi yang terstruktur bagi setiap pengguna sesuai hak aksesnya.

## 3. Ruang Lingkup Sistem

Sistem dikembangkan ke dalam enam modul utama sebagai berikut.

| Modul | Ruang Lingkup |
|---|---|
| Modul A — Vania | Autentikasi pengguna, pengelolaan program magang, permohonan magang institusi, verifikasi administrasi, rekomendasi, dan keputusan penerimaan. |
| Modul B — Shafa | Pengelolaan akun dan peran pengguna, pendaftaran rekrutmen magang DTI, serta pengelolaan dan pemeriksaan tugas magang. |
| Modul C — Zhahra | Pelaksanaan presensi reguler, pengajuan dan verifikasi presensi susulan, koreksi, serta rekapitulasi presensi. |
| Modul D — Ghina | Penetapan seksi dan mentor peserta magang serta pengajuan dan verifikasi izin ketidakhadiran. |
| Modul E — Dinda | Proses penyelesaian magang, pengelolaan dan unduh sertifikat, serta pemantauan program magang. |
| Modul F — Fajri | Pengelolaan dan pemeriksaan logbook harian serta pencatatan dan pengelolaan evaluasi peserta. |

### Pengguna Sistem

Sistem melibatkan beberapa jenis pengguna dengan hak akses yang disesuaikan terhadap tanggung jawab masing-masing, antara lain:

- Superadmin
- Mentor
- Kepala Seksi
- Direktur
- Peserta Magang
- Calon Peserta Magang

## 4. Teknologi yang Digunakan

Teknologi yang digunakan dalam pengembangan sistem meliputi:

| Komponen | Teknologi |
|---|---|
| Backend Framework | Laravel 12 |
| Bahasa Pemrograman Backend | PHP 8.2 atau lebih baru |
| Frontend | Blade Template, HTML, CSS, JavaScript |
| CSS Framework | Tailwind CSS 4 |
| Build Tool | Vite 6 |
| Database | MySQL (konfigurasi database menyesuaikan lingkungan pengembangan) |
| Dependency Management Backend | Composer |
| Dependency Management Frontend | NPM |
| Version Control | Git dan GitHub |
| Automated Testing | PHPUnit 11 |

## 5. Arsitektur Sistem

Sistem dikembangkan menggunakan pendekatan **Model-View-Controller (MVC)** yang disediakan oleh Laravel.

Gambaran arsitektur sistem:

```text
+-------------------------------+
|          Pengguna             |
| Superadmin, Mentor, Peserta,  |
| Kepala Seksi, Direktur        |
+---------------+---------------+
                |
                v
+-------------------------------+
|       Presentation Layer      |
|    Blade, HTML, CSS, JS       |
+---------------+---------------+
                |
                v
+-------------------------------+
|       Application Layer       |
|       Routes & Controller     |
|                               |
|  +-------------------------+  |
|  |       Services          |  |
|  | Business Logic / Alur   |  |
|  +-------------------------+  |
+---------------+---------------+
                |
                v
+-------------------------------+
|          Model Layer          |
|       Eloquent ORM            |
+---------------+---------------+
                |
                v
+-------------------------------+
|           Database            |
|             MySQL             |
+-------------------------------+
```

Struktur direktori utama:

```text
magangDTI/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   ├── Models/
│   └── Services/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── sql/
├── public/
│   ├── css/
│   └── js/
├── resources/
│   └── views/
├── routes/
├── storage/
├── tests/
├── .env.example
├── artisan
├── composer.json
├── package.json
└── README.md
```

## 6. Instalasi dan Menjalankan Aplikasi

### Prasyarat

Pastikan perangkat telah memiliki:

- PHP versi 8.2 atau lebih baru.
- Composer.
- Node.js dan NPM.
- MySQL atau database yang sesuai dengan konfigurasi proyek.
- Git.

### Langkah Instalasi

**1. Clone repository**

```bash
git clone https://github.com/justfira/magangDTI.git
cd magangDTI
```

**2. Install dependency PHP**

```bash
composer install
```

**3. Install dependency frontend**

```bash
npm install
```

**4. Buat file konfigurasi environment**

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Linux/macOS:

```bash
cp .env.example .env
```

**5. Generate application key**

```bash
php artisan key:generate
```

**6. Konfigurasi database**

Buka file `.env`, kemudian sesuaikan konfigurasi database dengan lingkungan lokal.

Contoh konfigurasi MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=magang_dti
DB_USERNAME=root
DB_PASSWORD=
```

Buat database `magang_dti` melalui MySQL sebelum menjalankan migrasi.

**7. Jalankan migrasi database**

```bash
php artisan migrate
```

Apabila tim menggunakan file SQL proyek sebagai sumber database, ikuti prosedur impor database yang telah disepakati oleh tim.

**8. Bersihkan cache konfigurasi**

```bash
php artisan optimize:clear
```

**9. Jalankan aplikasi**

Terminal pertama:

```bash
php artisan serve
```

Terminal kedua:

```bash
npm run dev
```

Aplikasi dapat diakses melalui:

```text
http://127.0.0.1:8000
```

Untuk membuat aset frontend versi produksi:

```bash
npm run build
```

## 7. Pengujian Sistem

Pengujian sistem dilakukan untuk memastikan setiap fitur berjalan sesuai kebutuhan dan proses bisnis yang telah ditentukan.

Metode pengujian yang direncanakan adalah **User Acceptance Testing (UAT)** dengan melibatkan pengguna atau perwakilan aktor yang berkaitan dengan fitur.

Ruang lingkup pengujian meliputi:

1. Pengujian autentikasi dan hak akses pengguna.
2. Pengujian pengelolaan program dan pendaftaran magang.
3. Pengujian administrasi dan penerimaan peserta.
4. Pengujian presensi dan izin ketidakhadiran.
5. Pengujian penempatan serta pengelolaan aktivitas magang.
6. Pengujian logbook, evaluasi, dan penyelesaian magang.

Pengujian otomatis dapat dijalankan menggunakan PHPUnit:

```bash
php artisan test
```

Hasil UAT didokumentasikan berdasarkan skenario pengujian, hasil yang diharapkan, hasil aktual, status pengujian, dan catatan dari pengguna.

## 8. Manajemen Proyek dan Kontribusi

Pengelolaan pengembangan sistem dilakukan menggunakan Git dan GitHub untuk mendukung kolaborasi, pembagian tugas, pengendalian versi, dan pemantauan progres.

### Manajemen Pekerjaan

GitHub Projects digunakan untuk:
- Mengelola backlog pekerjaan.
- Memantau status dan progres pengembangan.
- Mengatur jadwal melalui Timeline.
- Menghubungkan pekerjaan dengan Issues dan Pull Requests.

GitHub Issues digunakan untuk mencatat pekerjaan, pembagian tanggung jawab, pengujian, serta perbaikan sistem.

### Alur Kontribusi

Setiap anggota tim menggunakan branch masing-masing untuk mengembangkan fitur atau memperbaiki bagian sistem.

Contoh alur kontribusi:

```text
main
  |
  +-- vania
  |
  +-- shafa
  |
  +-- zhahra
  |
  +-- ghina
  |
  +-- dinda
  |
  +-- fajri
```

Tahapan kontribusi:

1. Sinkronisasi branch dengan repository utama.
2. Membuat atau berpindah ke branch masing-masing.
3. Melakukan pengembangan fitur sesuai tugas.
4. Menyimpan perubahan menggunakan commit.
5. Mengirim perubahan ke GitHub melalui push.
6. Membuat Pull Request dari branch anggota menuju `main`.
7. Melakukan pemeriksaan perubahan dan pengujian.
8. Melakukan merge setelah perubahan disetujui.

Contoh perintah Git:

```bash
git checkout nama-branch
git pull origin main

# Setelah melakukan perubahan
git add .
git commit -m "Deskripsi perubahan"
git push origin nama-branch
```

Pull Request digunakan sebagai mekanisme pemeriksaan perubahan sebelum kode digabungkan ke branch utama.

---

## Informasi Repository

**Repository:** https://github.com/justfira/magangDTI

**Organisasi/Tim:** Kelompok 12

**Mata Kuliah:** Project Pengembangam Sistem Informasi (PPSI)

**Status Proyek:** Dalam Pengembangan

---

*Dokumentasi ini disusun sebagai panduan pengenalan, instalasi, pengembangan, dan pengelolaan Magang DTI Management Project. Informasi dapat diperbarui mengikuti perkembangan fitur dan keputusan teknis tim.*
