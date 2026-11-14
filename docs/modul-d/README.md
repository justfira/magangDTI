# Modul D — Presensi, Izin, Rekap Kehadiran

**PIC:** Zhahra · **Branch:** `zhahra` · **Use case:** UC-08, UC-09, UC-10, UC-11
Diagram use case, activity, dan sequence: [diagram.md](diagram.md) (gambar SVG di [`img/`](img/)).

## 1. Status pekerjaan (GitHub Projects)

| Issue | Pekerjaan | Hasil |
|---|---|---|
| #79 | Alur check-in dan check-out | ✅ `Peserta\PresensiController` |
| #80 | Validasi lokasi / geolokasi | ✅ Geolocation API + `GeofenceService` |
| #88 | Presensi susulan | ✅ Peserta mengajukan, mentor memverifikasi |
| #89 | Pengajuan izin | ✅ Form izin + lampiran opsional |
| #90 | Persetujuan izin oleh mentor | ✅ Setujui/tolak, lihat lampiran |
| #91 | Koreksi presensi oleh admin | ✅ Koreksi + riwayat sebelum/sesudah |
| #97 | Rekap & ekspor kehadiran | ✅ Rekap bulanan + CSV |
| #101 | Diagram use case, activity, sequence | ✅ [diagram.md](diagram.md) |
| #103 | UI Modul D | ✅ `public/css/presensi.css`, ramah HP |
| #106 | Dokumentasi Modul D | ✅ Dokumen ini |

## 2. Fitur per aktor

### Peserta Magang
| Menu | Fitur |
|---|---|
| **Presensi** | Check-in & check-out sekali sehari. Lokasi diambil dari browser dan harus berada dalam radius kantor DTI. Waktu memakai **waktu server**. |
| **Izin & Presensi Susulan** | Ajukan **presensi susulan** (hari ini, setelah kembali dari tugas luar kantor) atau **izin** (Penuh/Sebagian, lampiran opsional). Lihat status & catatan mentor. |

### Mentor DTI
| Menu | Fitur |
|---|---|
| **Verifikasi Izin & Susulan** | Daftar pengajuan **peserta bimbingannya saja**, filter status/jenis, setujui (catatan opsional) atau tolak (catatan wajib), buka lampiran izin. |

### Superadmin
| Menu | Fitur |
|---|---|
| **Kelola Presensi** | Daftar presensi per tanggal, cari peserta, koreksi jam check-in/check-out dengan **alasan & bukti wajib**, riwayat koreksi. |
| **Rekap Bulanan** | Rekap per peserta per bulan + **Ekspor CSV**. |

## 3. Aturan bisnis yang diterapkan

| Aturan | Sumber | Tempat di kode |
|---|---|---|
| Hanya peserta berstatus **Aktif**, dalam **periode magang**, dan **sudah ditempatkan** yang boleh presensi/mengajukan | UC-08 E3, UC-11 E1/E3 | `KehadiranService::alasanTidakBoleh()` |
| Lokasi wajib; di luar radius atau akurasi buruk ditolak | UC-08 E1/E2, UC-09 E3 | `KehadiranService::periksaLokasi()` + `GeofenceService` |
| **Satu baris presensi per peserta per tanggal**; check-out mengisi baris yang sama | UC-08, NFR-07 | UNIQUE `(id_peserta_magang, tanggal)` |
| Check-out tanpa check-in ditolak | UC-08 E4 | `PresensiController::checkOut()` |
| Susulan hanya untuk hari ini, belum tercatat hadir, alasan wajib | UC-09 E1/E2/E5 | `PengajuanKehadiranController::simpanSusulan()` |
| Susulan disetujui → **1 baris presensi** (sumber `SUSULAN`), dalam satu transaksi | UC-09 langkah 14–15 | `VerifikasiKehadiranController::setujui()` |
| Izin tidak boleh pada tanggal yang sudah tercatat hadir; dicek ulang saat disetujui | UC-11 E2 | `simpanIzin()` & `setujui()` |
| **Izin Penuh** yang disetujui memblokir check-in di tanggal itu; izin Sebagian tidak | UC-11 | `KehadiranService::izinPenuhDisetujui()` |
| Mentor hanya memproses peserta bimbingannya (selain itu 403) | UC-09 E4, UC-11 E4 | `Controller::pastikanBimbingan()` |
| Koreksi wajib alasan & bukti; data sebelum/sesudah disimpan | UC-10 A1, E1 | `KelolaPresensiController::simpanKoreksi()` |
| Hari hadir = reguler lengkap + susulan + koreksi; izin **tidak** dihitung hadir | UC-10 langkah 7–10 | `KehadiranService::rekapBulanan()` |
| Lampiran izin & bukti koreksi disimpan **privat** (tidak bisa dibuka lewat URL publik) | keamanan | disk `local` (`storage/app/private`) |

## 4. Route

| Method | URL | Peran | Keterangan |
|---|---|---|---|
| GET | `/peserta/presensi` | Peserta | Presensi harian |
| POST | `/peserta/presensi/check-in` | Peserta | Check-in (latitude, longitude, akurasi) |
| POST | `/peserta/presensi/check-out` | Peserta | Check-out |
| GET | `/peserta/pengajuan-kehadiran` | Peserta | Riwayat izin & susulan |
| GET | `/peserta/pengajuan-kehadiran/create?jenis=SUSULAN\|IZIN` | Peserta | Form pengajuan |
| POST | `/peserta/pengajuan-kehadiran` | Peserta | Simpan pengajuan |
| GET | `/mentor/verifikasi-kehadiran?status=&jenis=` | Mentor | Daftar pengajuan bimbingan |
| POST | `/mentor/verifikasi-kehadiran/{id}/setujui` | Mentor | Setujui |
| POST | `/mentor/verifikasi-kehadiran/{id}/tolak` | Mentor | Tolak (catatan wajib) |
| GET | `/mentor/verifikasi-kehadiran/{id}/lampiran` | Mentor | Buka lampiran izin |
| GET | `/superadmin/presensi?tanggal=&cari=` | Superadmin | Kelola presensi |
| GET/POST | `/superadmin/presensi/{id}/koreksi` | Superadmin | Form & simpan koreksi |
| GET | `/superadmin/koreksi-presensi/{id}/bukti` | Superadmin | Bukti koreksi |
| GET | `/superadmin/presensi/rekap?bulan=YYYY-MM` | Superadmin | Rekap bulanan |
| GET | `/superadmin/presensi/rekap/csv?bulan=YYYY-MM` | Superadmin | Unduh CSV |

## 5. File yang dibuat/diubah

```
app/Services/KehadiranService.php                     aturan kehadiran bersama + rekap
app/Http/Controllers/Peserta/PresensiController.php    UC-08
app/Http/Controllers/Peserta/PengajuanKehadiranController.php   UC-09 & UC-11 (peserta)
app/Http/Controllers/Mentor/VerifikasiKehadiranController.php   UC-09 & UC-11 (mentor)
app/Http/Controllers/Superadmin/KelolaPresensiController.php    UC-10
resources/views/peserta/presensi/       index, _lokasi (geolokasi), _badge (status)
resources/views/peserta/kehadiran/      index, create_susulan, create_izin
resources/views/mentor/kehadiran/       index
resources/views/superadmin/presensi/    index, koreksi, rekap
public/css/presensi.css                 gaya khusus Modul D (kelas pr-*)
routes/web.php                          + route lampiran izin & bukti koreksi
tests/Feature/ModulD/                   7 berkas test (43 test)
docs/modul-d/                           dokumen ini + diagram
```

Tidak ada perubahan migrasi/tabel: Modul D memakai tabel `presensi`, `pengajuan_kehadiran`, dan `koreksi_presensi` dari ERD revisi.

## 6. Cara mencoba

1. Pastikan `.env` memakai MySQL dan berisi pengaturan kantor (lihat `.env.example`):
   ```
   KANTOR_LAT=-0.9147
   KANTOR_LONG=100.4583
   KANTOR_RADIUS_METER=100
   KANTOR_BATAS_AKURASI_METER=50
   ```
   Nilai radius & akurasi masih **menunggu konfirmasi DTI**. Saat uji coba di luar kantor, ganti `KANTOR_LAT`/`KANTOR_LONG` dengan koordinat lokasi Anda sendiri (bisa dilihat di Google Maps), lalu jalankan `php artisan config:clear`.
2. Isi ulang data contoh: `php artisan migrate:fresh --seed`
3. Jalankan `php artisan serve`, buka **`http://127.0.0.1:8000`** (bukan `http://magang-dti.test`, lihat catatan di bawah).
4. Akun (password `password`):
   - `peserta@mail.test`: menu **Presensi** dan **Izin & Presensi Susulan**
   - `mentor@dti.test`: menu **Verifikasi Izin & Susulan**
   - `superadmin@dti.test`: menu **Kelola Presensi** → **Rekap Bulanan**

> **Penting: geolokasi butuh HTTPS.** Browser hanya mengizinkan akses lokasi di `https://` atau `localhost`/`127.0.0.1`.
> Di Laragon, `http://magang-dti.test` **tidak** akan bisa mengambil lokasi. Pilih salah satu:
> - pakai `php artisan serve` lalu buka `http://127.0.0.1:8000`, atau
> - aktifkan SSL Laragon (Menu → Apache → SSL → Enabled) lalu buka `https://magang-dti.test`.
> Untuk server produksi DTI, sistem wajib memakai HTTPS.

## 7. Pengujian

```bash
php artisan test --filter=ModulD
```

| Berkas test | Yang diuji |
|---|---|
| `PresensiRegulerTest` | check-in/out satu baris, check-in ganda, check-out tanpa check-in, peserta tidak aktif, calon ditolak |
| `GeolokasiPresensiTest` | tanpa lokasi, di luar radius, akurasi buruk, koordinat tersimpan, rumus haversine |
| `PresensiSusulanTest` | aturan pengajuan, persetujuan membuat 1 presensi, tolak wajib catatan, mentor lain 403 |
| `PengajuanIzinTest` | lampiran opsional/privat, tipe berkas, periode magang, izin ganda |
| `PersetujuanIzinTest` | izin tidak dihitung hadir, izin penuh memblokir check-in, cek ulang kehadiran, akses lampiran |
| `KoreksiPresensiTest` | riwayat sebelum/sesudah, alasan & bukti wajib, hanya Superadmin |
| `RekapKehadiranTest` | perhitungan hari hadir & izin, ekspor CSV, periode kosong |

Selain test otomatis, seluruh alur sudah dicoba di browser (MariaDB, lokasi disimulasikan):
check-in → check-out → lokasi di luar radius ditolak → presensi susulan → izin dengan lampiran → mentor menyetujui → koreksi presensi → rekap & CSV.

## 8. Catatan untuk tim

- **Kelas CSS** Modul D ada di `public/css/presensi.css` dengan awalan `pr-`, dipanggil langsung dari view Modul D. Tidak mengubah `app.css` / layout, jadi aman digabung dengan pembaruan tampilan di branch `fajri`.
- **Format CSV** memakai pemisah `;` dan UTF-8 BOM agar langsung terbuka rapi di Excel berbahasa Indonesia.
- **Belum termasuk (usulan lanjutan):**
  - Menambah presensi baru oleh Superadmin untuk tanggal yang sama sekali tidak tercatat. Koreksi saat ini hanya untuk presensi yang sudah ada, sesuai UC-10 A1.
  - Hari libur nasional belum diperhitungkan di rekap.
- **Perbedaan dengan lampiran A3:** di A3, UC-11 (izin) tercantum atas nama Ghina, sedangkan di GitHub Projects masuk ke Modul D (Zhahra). Samakan salah satunya.
