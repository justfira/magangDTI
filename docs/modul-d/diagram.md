# Diagram Modul D — Presensi, Izin, Rekap Kehadiran

Diagram ditulis dalam **Mermaid** (GitHub menampilkannya otomatis). Versi gambar SVG untuk dokumen ada di folder [`img/`](img/).
Isi diagram mengikuti implementasi di branch ini (controller, service, tabel, dan pesan galat).

| Kode | Use case | Aktor |
|---|---|---|
| UC-08 | Melakukan Presensi Reguler | Peserta Magang |
| UC-09 | Mengajukan dan Memverifikasi Presensi Susulan | Peserta Magang, Mentor DTI |
| UC-10 | Mengoreksi dan Merekap Presensi | Superadmin |
| UC-11 | Mengajukan dan Memverifikasi Izin | Peserta Magang, Mentor DTI |

---

## 1. Use Case Diagram Modul D

```mermaid
flowchart LR
    peserta(["👤 Peserta Magang"])
    mentor(["👤 Mentor DTI"])
    admin(["👤 Superadmin"])

    subgraph sistem["Sistem Pengelolaan Magang DTI — Modul D"]
        uc08a(["Check-in"])
        uc08b(["Check-out"])
        geo(["Validasi lokasi (geofence)"])
        uc09a(["Ajukan presensi susulan"])
        uc09b(["Verifikasi presensi susulan"])
        uc11a(["Ajukan izin"])
        uc11b(["Verifikasi izin"])
        uc10a(["Koreksi presensi"])
        uc10b(["Rekap kehadiran bulanan"])
        uc10c(["Ekspor CSV"])
        riwayat(["Lihat status & riwayat"])
    end

    peserta --- uc08a & uc08b & uc09a & uc11a & riwayat
    uc09b & uc11b --- mentor
    uc10a & uc10b --- admin

    uc08a -. "«include»" .-> geo
    uc08b -. "«include»" .-> geo
    uc09a -. "«include»" .-> geo
    uc10c -. "«extend»" .-> uc10b
```

---

## 2. Activity Diagram

### 2.1 UC-08 Presensi Reguler (Check-in & Check-out)

```mermaid
flowchart TD
    mulai((Mulai)) --> buka["Peserta membuka halaman Presensi"]
    buka --> tampil["Sistem menampilkan status presensi hari ini"]
    tampil --> pilih{"Tombol yang dipilih?"}

    pilih -- Check-in --> izinLok["Browser meminta izin lokasi"]
    pilih -- Check-out --> izinLok
    izinLok --> diberi{"Izin lokasi diberikan?"}
    diberi -- Tidak --> e1["Tampilkan: lokasi diperlukan untuk presensi (E1)"] --> selesai((Selesai))
    diberi -- Ya --> kirim["Kirim koordinat & akurasi ke server"]

    kirim --> aktif{"Status magang Aktif, dalam periode,<br/>sudah ditempatkan?"}
    aktif -- Tidak --> e3["Tolak presensi (E3)"] --> selesai
    aktif -- Ya --> jenis{"Check-in atau check-out?"}

    jenis -- Check-in --> sudah{"Sudah ada presensi hari ini?"}
    sudah -- Ya --> a1["Tampilkan: sudah check-in (A1)"] --> selesai
    sudah -- Tidak --> izinPenuh{"Ada izin penuh disetujui hari ini?"}
    izinPenuh -- Ya --> tolakIzin["Tolak check-in"] --> selesai
    izinPenuh -- Tidak --> cekLok1{"Dalam radius & akurasi memenuhi?"}
    cekLok1 -- Tidak --> e2["Tolak: lokasi tidak valid (E2)"] --> selesai
    cekLok1 -- Ya --> simpanIn["Buat baris presensi (REGULER),<br/>waktu_checkin = waktu server"]
    simpanIn --> okIn["Tampilkan: Check-in berhasil"] --> selesai

    jenis -- Check-out --> adaIn{"Sudah check-in hari ini?"}
    adaIn -- Tidak --> e4["Tolak: check-out tanpa check-in (E4)"] --> selesai
    adaIn -- Ya --> cekLok2{"Dalam radius & akurasi memenuhi?"}
    cekLok2 -- Tidak --> e2
    cekLok2 -- Ya --> simpanOut["Isi waktu_checkout pada baris yang sama"]
    simpanOut --> okOut["Tampilkan: Check-out berhasil"] --> selesai
```

### 2.2 UC-09 Presensi Susulan

```mermaid
flowchart TD
    subgraph P["Peserta Magang"]
        p1["Buka form presensi susulan"] --> p2["Isi alasan, tekan Ajukan"]
    end
    subgraph S["Sistem"]
        s1{"Tanggal = hari ini?"}
        s2{"Belum tercatat hadir &<br/>belum ada pengajuan aktif?"}
        s3{"Lokasi valid di kantor?"}
        s4["Simpan pengajuan<br/>(Menunggu Verifikasi, waktu server)"]
        s5["Notifikasi ke mentor"]
        s6{"Cek ulang: peserta Aktif &<br/>belum tercatat hadir?"}
        s7["Transaksi: status Disetujui +<br/>buat presensi (sumber SUSULAN)"]
        s8["Status Ditolak + catatan"]
        s9["Notifikasi hasil ke peserta"]
        tolak["Tampilkan pesan penolakan"]
    end
    subgraph M["Mentor DTI"]
        m1["Buka Verifikasi Izin & Susulan"] --> m2{"Keputusan?"}
    end

    p2 --> s1
    s1 -- Tidak --> tolak
    s1 -- Ya --> s2
    s2 -- Tidak --> tolak
    s2 -- Ya --> s3
    s3 -- Tidak --> tolak
    s3 -- Ya --> s4 --> s5 --> m1
    m2 -- Setujui --> s6
    s6 -- Tidak --> tolak
    s6 -- Ya --> s7 --> s9
    m2 -- "Tolak (catatan wajib)" --> s8 --> s9
```

### 2.3 UC-11 Pengajuan & Persetujuan Izin

```mermaid
flowchart TD
    subgraph P["Peserta Magang"]
        p1["Buka form izin"] --> p2["Pilih tanggal, jenis (Penuh/Sebagian),<br/>isi alasan, lampiran opsional"]
    end
    subgraph S["Sistem"]
        s1{"Alasan terisi & lampiran<br/>PDF/JPG/PNG ≤ 2 MB?"}
        s2{"Peserta Aktif & tanggal<br/>dalam periode magang?"}
        s3{"Tanggal belum tercatat hadir &<br/>belum ada pengajuan aktif?"}
        s4["Simpan izin (Menunggu Verifikasi),<br/>lampiran di disk privat"]
        s5["Notifikasi ke mentor"]
        s6{"Cek ulang: belum tercatat hadir?"}
        s7["Status Disetujui<br/>(tidak dihitung hadir)"]
        s8["Status Ditolak + catatan"]
        s9["Notifikasi hasil ke peserta"]
        tolak["Tampilkan pesan penolakan"]
    end
    subgraph M["Mentor DTI"]
        m1["Periksa alasan & lampiran"] --> m2{"Keputusan?"}
    end

    p2 --> s1
    s1 -- Tidak --> tolak
    s1 -- Ya --> s2
    s2 -- Tidak --> tolak
    s2 -- Ya --> s3
    s3 -- Tidak --> tolak
    s3 -- Ya --> s4 --> s5 --> m1
    m2 -- Setujui --> s6
    s6 -- "Tidak (E2)" --> tolak
    s6 -- Ya --> s7 --> s9
    m2 -- "Tolak (catatan wajib)" --> s8 --> s9
```

### 2.4 UC-10 Koreksi & Rekap Presensi

```mermaid
flowchart TD
    mulai((Mulai)) --> buka["Superadmin membuka Kelola Presensi"]
    buka --> pilih{"Menu?"}

    pilih -- "Koreksi Presensi (A1)" --> k1["Pilih presensi bermasalah"]
    k1 --> k2["Ubah jam, isi alasan, unggah bukti"]
    k2 --> k3{"Alasan & bukti ada,<br/>check-out setelah check-in?"}
    k3 -- Tidak --> k4["Tolak koreksi (E1/E2)"] --> selesai((Selesai))
    k3 -- Ya --> k5["Transaksi: perbarui presensi (sumber KOREKSI) +<br/>simpan riwayat sebelum/sesudah"]
    k5 --> k6["Notifikasi ke peserta"] --> selesai

    pilih -- Rekap Kehadiran --> r1["Pilih periode bulan"]
    r1 --> r2["Hitung per peserta: hadir = reguler lengkap +<br/>susulan + koreksi; izin terpisah"]
    r2 --> r3{"Ada data?"}
    r3 -- Tidak --> r4["Tampilkan: data rekap tidak tersedia (E3)"] --> selesai
    r3 -- Ya --> r5["Tampilkan rekap"]
    r5 --> r6{"Ekspor CSV?"}
    r6 -- "Tidak (A2)" --> selesai
    r6 -- Ya --> r7["Unduh rekap-kehadiran-YYYY-MM.csv"] --> selesai
```

---

## 3. Sequence Diagram

### 3.1 UC-08 Check-in

```mermaid
sequenceDiagram
    actor P as Peserta
    participant B as Browser (Geolocation API)
    participant C as PresensiController
    participant K as KehadiranService
    participant G as GeofenceService
    participant DB as Database (presensi)

    P->>B: Tekan "Check-in"
    B->>P: Minta izin lokasi
    P-->>B: Izinkan
    B->>C: POST /peserta/presensi/check-in (latitude, longitude, akurasi)
    C->>K: alasanTidakBoleh(peserta, hari ini)
    K-->>C: null (Aktif, dalam periode, sudah ditempatkan)
    C->>K: sudahTercatat() / izinPenuhDisetujui()
    K-->>C: false
    C->>K: periksaLokasi(input)
    K->>G: periksa(lat, lon, akurasi)
    G-->>K: valid, jarak 35 m
    K-->>C: data lokasi
    C->>DB: INSERT presensi (REGULER, waktu_checkin = now())
    alt sudah ada baris hari ini (UNIQUE)
        DB-->>C: UniqueConstraintViolation
        C-->>P: "Anda sudah check-in hari ini"
    else berhasil
        DB-->>C: id_presensi
        C-->>P: "Check-in berhasil pukul 08:02"
    end
```

### 3.2 UC-09 Presensi Susulan

```mermaid
sequenceDiagram
    actor P as Peserta
    participant PC as PengajuanKehadiranController
    actor M as Mentor
    participant VC as VerifikasiKehadiranController
    participant K as KehadiranService
    participant DB as Database

    P->>PC: POST pengajuan (SUSULAN, alasan, lokasi)
    PC->>K: alasanTidakBoleh / sudahTercatat / adaPengajuanAktif / periksaLokasi
    K-->>PC: semua lolos
    PC->>DB: INSERT pengajuan_kehadiran (Menunggu Verifikasi)
    PC->>DB: INSERT notifikasi (ke mentor)
    PC-->>P: "Presensi susulan berhasil diajukan"

    M->>VC: POST .../setujui
    VC->>VC: pastikanBimbingan(peserta)
    VC->>K: alasanTidakBoleh / sudahTercatat (cek ulang)
    K-->>VC: lolos
    rect rgb(235, 245, 255)
    note over VC,DB: DB::transaction
    VC->>DB: UPDATE pengajuan_kehadiran SET status = Disetujui
    VC->>DB: INSERT presensi (sumber SUSULAN, id_pengajuan_kehadiran)
    end
    VC->>DB: INSERT notifikasi (ke peserta)
    VC-->>M: "Pengajuan presensi susulan disetujui"
```

### 3.3 UC-11 Izin

```mermaid
sequenceDiagram
    actor P as Peserta
    participant PC as PengajuanKehadiranController
    participant FS as Storage (disk privat)
    actor M as Mentor
    participant VC as VerifikasiKehadiranController
    participant DB as Database

    P->>PC: POST pengajuan (IZIN, tanggal, jenis_izin, alasan, lampiran?)
    PC->>PC: validasi + cek periode & kehadiran
    opt ada lampiran
        PC->>FS: simpan izin/{id_peserta}/...
    end
    PC->>DB: INSERT pengajuan_kehadiran (Menunggu Verifikasi)
    PC->>DB: INSERT notifikasi (ke mentor)

    M->>VC: GET .../lampiran
    VC->>VC: pastikanBimbingan(peserta)
    VC->>FS: baca berkas
    FS-->>M: tampilkan lampiran
    M->>VC: POST .../setujui atau .../tolak (catatan)
    VC->>DB: cek ulang presensi pada tanggal izin
    VC->>DB: UPDATE status (Disetujui / Ditolak)
    VC->>DB: INSERT notifikasi (ke peserta)
```

### 3.4 UC-10 Koreksi & Rekap

```mermaid
sequenceDiagram
    actor A as Superadmin
    participant KC as KelolaPresensiController
    participant K as KehadiranService
    participant FS as Storage (disk privat)
    participant DB as Database

    A->>KC: POST /superadmin/presensi/{id}/koreksi (jam, alasan, bukti)
    KC->>KC: validasi (alasan & bukti wajib, check-out > check-in)
    KC->>FS: simpan bukti
    rect rgb(235, 245, 255)
    note over KC,DB: DB::transaction
    KC->>DB: UPDATE presensi (sumber KOREKSI)
    KC->>DB: INSERT koreksi_presensi (data_sebelum, data_sesudah, id_pengoreksi)
    end
    KC->>DB: INSERT notifikasi (ke peserta)
    KC-->>A: "Koreksi presensi berhasil disimpan"

    A->>KC: GET /superadmin/presensi/rekap?bulan=2026-11
    KC->>K: rekapBulanan(2026, 11)
    K->>DB: hitung per peserta (hadir, susulan, izin, belum check-out)
    DB-->>K: hasil
    K-->>KC: koleksi rekap
    KC-->>A: tabel rekap
    A->>KC: GET /superadmin/presensi/rekap/csv?bulan=2026-11
    KC-->>A: rekap-kehadiran-2026-11.csv
```
