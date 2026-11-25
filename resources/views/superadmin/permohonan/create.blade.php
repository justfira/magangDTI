@extends('layouts.app')
@section('judul', 'Tambah Permohonan Institusi')
@section('konten')

{{-- ===== KEPALA HALAMAN ===== --}}
<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('superadmin.permohonan.index') }}">
            <x-ikon nama="kembali" /> Kembali ke daftar permohonan
        </a>
        <h1>Tambah Permohonan Institusi</h1>
        <p class="sub-judul">
            Catat permohonan magang yang diterima dari institusi melalui surat resmi.
            Isi identitas institusi, data surat, dan daftar calon peserta.
        </p>
    </div>
</div>

<form
    id="form-permohonan"
    method="POST"
    action="{{ route('superadmin.permohonan.store') }}"
    enctype="multipart/form-data"
    novalidate
>
@csrf

{{-- ===== BAGIAN 1: INFORMASI INSTITUSI ===== --}}
<div class="kartu form form-kartu">
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2>
                <span class="ikon-bulat"><x-ikon nama="gedung" /></span>
                Informasi Institusi
            </h2>
            <p>Identitas institusi pengirim surat permohonan.</p>
        </div>
        <div class="form-bagian-isi">

            {{-- nama_institusi: varchar(180) NOT NULL --}}
            <label>
                Nama Institusi <span class="label-wajib">*</span>
                <input
                    type="text"
                    name="nama_institusi"
                    id="nama_institusi"
                    value="{{ old('nama_institusi') }}"
                    maxlength="180"
                    placeholder="mis. Universitas Andalas"
                    required
                    autofocus
                >
                @error('nama_institusi')
                    <small class="teks-gagal">{{ $message }}</small>
                @enderror
            </label>

            {{-- kontak_institusi: varchar(150) NULLABLE --}}
            <label>
                Kontak / PIC Institusi
                <input
                    type="text"
                    name="kontak_institusi"
                    id="kontak_institusi"
                    value="{{ old('kontak_institusi') }}"
                    maxlength="150"
                    placeholder="mis. Biro Akademik — 081234567890"
                >
                <small class="redup">Nama, jabatan, atau nomor kontak penanggung jawab surat.</small>
                @error('kontak_institusi')
                    <small class="teks-gagal">{{ $message }}</small>
                @enderror
            </label>

        </div>
    </div>
</div>

{{-- ===== BAGIAN 2: INFORMASI SURAT PERMOHONAN ===== --}}
<div class="kartu form form-kartu">
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2>
                <span class="ikon-bulat kuning"><x-ikon nama="berkas" /></span>
                Informasi Surat Permohonan
            </h2>
            <p>Nomor, tanggal surat, dan scan dokumen surat permohonan resmi.</p>
        </div>
        <div class="form-bagian-isi">

            <div class="dua-kolom">
                {{-- nomor_surat: varchar(100) NOT NULL, UNIQUE --}}
                <label>
                    Nomor Surat <span class="label-wajib">*</span>
                    <input
                        type="text"
                        name="nomor_surat"
                        id="nomor_surat"
                        value="{{ old('nomor_surat') }}"
                        maxlength="100"
                        placeholder="mis. 123/UN16/KM/2026"
                        required
                    >
                    @error('nomor_surat')
                        <small class="teks-gagal">{{ $message }}</small>
                    @enderror
                </label>

                {{-- tanggal_surat: date NULLABLE --}}
                <label>
                    Tanggal Surat <span class="label-wajib">*</span>
                    <input
                        type="date"
                        name="tanggal_surat"
                        id="tanggal_surat"
                        value="{{ old('tanggal_surat') }}"
                        max="{{ date('Y-m-d') }}"
                        required
                    >
                    @error('tanggal_surat')
                        <small class="teks-gagal">{{ $message }}</small>
                    @enderror
                </label>
            </div>

            {{-- Upload scan surat (dokumen_pengajuan: jenis_dokumen = SURAT_PENGAJUAN) --}}
            <label>
                Scan / File Surat Permohonan <span class="label-wajib">*</span>
                <div class="zona-unggah" id="zona-unggah">
                    <x-ikon nama="berkas" />
                    <div>
                        <b id="label-file">Pilih atau seret file ke sini</b>
                        <small class="redup">PDF, JPG, JPEG, atau PNG &mdash; maks. 5 MB</small>
                    </div>
                    <input
                        type="file"
                        name="surat"
                        id="input-surat"
                        accept=".pdf,.jpg,.jpeg,.png"
                        required
                        style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%"
                    >
                </div>
                <div id="info-file" class="info-kotak" style="display:none;margin-top:8px">
                    <x-ikon nama="berkas" />
                    <span id="nama-file-dipilih"></span>
                    <span id="ukuran-file-dipilih" class="redup" style="margin-left:6px"></span>
                </div>
                @error('surat')
                    <small class="teks-gagal">{{ $message }}</small>
                @enderror
            </label>

        </div>
    </div>
</div>

{{-- ===== BAGIAN 3: DATA CALON PESERTA ===== --}}
<div class="kartu">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat olive"><x-ikon nama="pengguna" /></span>
            <div>
                <h2>Data Calon Peserta</h2>
                <p>Tambahkan minimal satu calon peserta. Gunakan tombol <b>+ Tambah Peserta</b> untuk baris baru.</p>
            </div>
        </div>
        <div>
            <button type="button" class="btn" id="tombol-tambah-calon">
                <x-ikon nama="tambah-orang" /> Tambah Peserta
            </button>
        </div>
    </div>

    <div id="area-calon" style="overflow-x:auto">
        <table id="tabel-calon" style="width:100%;border-collapse:collapse;min-width:900px">
            <thead>
                <tr style="background:var(--kepala-tabel,#f1f5f1)">
                    <th style="padding:10px 12px;text-align:center;width:40px;font-size:12.5px;color:var(--redup)">#</th>
                    <th style="padding:10px 12px;font-size:12.5px;text-align:left">
                        Nama Lengkap <span class="label-wajib">*</span>
                    </th>
                    <th style="padding:10px 12px;font-size:12.5px;text-align:left">Email</th>
                    <th style="padding:10px 12px;font-size:12.5px;text-align:left">No. HP</th>
                    <th style="padding:10px 12px;font-size:12.5px;text-align:left">NIM / NIS</th>
                    <th style="padding:10px 12px;font-size:12.5px;text-align:left">Institusi Asal</th>
                    <th style="padding:10px 12px;font-size:12.5px;text-align:left">Jurusan / Prodi</th>
                    <th style="padding:10px 12px;text-align:center;width:60px"></th>
                </tr>
            </thead>
            <tbody id="tbody-calon">
                {{-- Baris pertama: dirender Blade (mendukung old() setelah validasi gagal) --}}
                @php $barisBlade = old('calon', [[]]); @endphp
                @foreach ($barisBlade as $i => $c)
                <tr class="baris-calon" data-index="{{ $i }}">
                    <td style="padding:8px 12px;text-align:center;font-weight:600;color:var(--redup);font-size:13px" class="nomor-urut">
                        {{ $loop->iteration }}
                    </td>
                    {{-- nama_lengkap: varchar(150) NOT NULL --}}
                    <td style="padding:6px 8px">
                        <input
                            type="text"
                            name="calon[{{ $i }}][nama_lengkap]"
                            value="{{ $c['nama_lengkap'] ?? '' }}"
                            maxlength="150"
                            placeholder="Nama lengkap"
                            required
                            style="width:100%;min-width:160px"
                        >
                    </td>
                    {{-- email: varchar(191) NULLABLE --}}
                    <td style="padding:6px 8px">
                        <input
                            type="email"
                            name="calon[{{ $i }}][email]"
                            value="{{ $c['email'] ?? '' }}"
                            maxlength="191"
                            placeholder="email@institusi.ac.id"
                            style="width:100%;min-width:160px"
                        >
                    </td>
                    {{-- no_hp: varchar(30) NULLABLE --}}
                    <td style="padding:6px 8px">
                        <input
                            type="text"
                            name="calon[{{ $i }}][no_hp]"
                            value="{{ $c['no_hp'] ?? '' }}"
                            maxlength="30"
                            placeholder="08xx"
                            style="width:100%;min-width:110px"
                        >
                    </td>
                    {{-- nomor_induk_pendidikan: varchar(40) NULLABLE --}}
                    <td style="padding:6px 8px">
                        <input
                            type="text"
                            name="calon[{{ $i }}][nomor_induk_pendidikan]"
                            value="{{ $c['nomor_induk_pendidikan'] ?? '' }}"
                            maxlength="40"
                            placeholder="NIM / NIS"
                            style="width:100%;min-width:110px"
                        >
                    </td>
                    {{-- institusi_asal: varchar(180) NULLABLE --}}
                    <td style="padding:6px 8px">
                        <input
                            type="text"
                            name="calon[{{ $i }}][institusi_asal]"
                            value="{{ $c['institusi_asal'] ?? '' }}"
                            maxlength="180"
                            placeholder="Universitas / Sekolah"
                            style="width:100%;min-width:150px"
                        >
                    </td>
                    {{-- jurusan_prodi: varchar(150) NULLABLE --}}
                    <td style="padding:6px 8px">
                        <input
                            type="text"
                            name="calon[{{ $i }}][jurusan_prodi]"
                            value="{{ $c['jurusan_prodi'] ?? '' }}"
                            maxlength="150"
                            placeholder="Jurusan / Prodi"
                            style="width:100%;min-width:150px"
                        >
                    </td>
                    <td style="padding:6px 8px;text-align:center">
                        <button
                            type="button"
                            class="btn-hapus-calon"
                            title="Hapus peserta ini"
                            aria-label="Hapus peserta {{ $loop->iteration }}"
                        >
                            <x-ikon nama="silang" />
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div id="pesan-min-calon" class="alert alert-gagal" style="display:none;margin:12px 22px 0">
        <x-ikon nama="peringatan" />
        <div>Minimal satu calon peserta harus diisi.</div>
    </div>

    <p style="padding:12px 22px;font-size:12.5px;color:var(--redup);border-top:1px solid var(--garis);margin:0">
        <x-ikon nama="info" style="width:13px;height:13px;vertical-align:middle" />
        Kolom <b>Nama Lengkap</b> wajib diisi. Kolom lain bersifat opsional namun sangat dianjurkan dilengkapi.
    </p>
</div>

{{-- ===== TOMBOL AKSI ===== --}}
<div class="form-kaki" style="margin-top:0">
    <a class="btn btn-halus" href="{{ route('superadmin.permohonan.index') }}">
        <x-ikon nama="kembali" /> Batal
    </a>
    <button type="submit" class="btn" id="tombol-simpan">
        <x-ikon nama="centang-saja" /> Simpan Permohonan
    </button>
</div>

</form>

{{-- ===== TEMPLATE BARIS CALON (tersembunyi, diklon JS) ===== --}}
<template id="tmpl-baris-calon">
    <tr class="baris-calon">
        <td style="padding:8px 12px;text-align:center;font-weight:600;color:var(--redup);font-size:13px" class="nomor-urut"></td>
        <td style="padding:6px 8px">
            <input type="text" name="" maxlength="150" placeholder="Nama lengkap" required style="width:100%;min-width:160px">
        </td>
        <td style="padding:6px 8px">
            <input type="email" name="" maxlength="191" placeholder="email@institusi.ac.id" style="width:100%;min-width:160px">
        </td>
        <td style="padding:6px 8px">
            <input type="text" name="" maxlength="30" placeholder="08xx" style="width:100%;min-width:110px">
        </td>
        <td style="padding:6px 8px">
            <input type="text" name="" maxlength="40" placeholder="NIM / NIS" style="width:100%;min-width:110px">
        </td>
        <td style="padding:6px 8px">
            <input type="text" name="" maxlength="180" placeholder="Universitas / Sekolah" style="width:100%;min-width:150px">
        </td>
        <td style="padding:6px 8px">
            <input type="text" name="" maxlength="150" placeholder="Jurusan / Prodi" style="width:100%;min-width:150px">
        </td>
        <td style="padding:6px 8px;text-align:center">
            <button type="button" class="btn-hapus-calon" title="Hapus peserta ini">
                <svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM9 9l6 6M15 9l-6 6"/></svg>
            </button>
        </td>
    </tr>
</template>

{{-- ===== STYLES HALAMAN ===== --}}
<style>
/* Label wajib */
.label-wajib { color: var(--gagal, #dc2626); }

/* Teks error per field */
.teks-gagal { color: var(--gagal, #dc2626); display:block; margin-top:3px; font-size:12px; }

/* Zona upload surat */
.zona-unggah {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px 22px;
    border: 2px dashed var(--garis, #d1d5db);
    border-radius: 10px;
    background: var(--bg, #fafaf8);
    cursor: pointer;
    transition: border-color .2s, background .2s;
    margin-top: 6px;
}
.zona-unggah:hover,
.zona-unggah.aktif {
    border-color: var(--utama, #166534);
    background: rgba(22, 101, 52, .04);
}
.zona-unggah .ikon { width: 28px; height: 28px; color: var(--redup, #6b7280); flex-shrink: 0; }

/* Tombol hapus baris calon */
.btn-hapus-calon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px; height: 30px;
    border: 1px solid var(--garis, #d1d5db);
    border-radius: 6px;
    background: transparent;
    color: var(--redup, #6b7280);
    cursor: pointer;
    transition: background .15s, color .15s, border-color .15s;
}
.btn-hapus-calon:hover {
    background: rgba(220, 38, 38, .08);
    color: var(--gagal, #dc2626);
    border-color: var(--gagal, #dc2626);
}
.btn-hapus-calon .ikon { width: 15px; height: 15px; }

/* Input di dalam tabel calon */
#tabel-calon input {
    padding: 6px 10px;
    border: 1px solid var(--garis, #d1d5db);
    border-radius: 6px;
    font-size: 13px;
    background: #fff;
    transition: border-color .15s;
    box-sizing: border-box;
}
#tabel-calon input:focus {
    outline: none;
    border-color: var(--utama, #166534);
    box-shadow: 0 0 0 2px rgba(22,101,52,.12);
}
#tabel-calon thead tr {
    border-bottom: 2px solid var(--garis, #e5e7eb);
}
#tabel-calon .baris-calon {
    border-bottom: 1px solid var(--garis, #f3f4f6);
    transition: background .1s;
}
#tabel-calon .baris-calon:hover { background: var(--bg, #fafaf8); }
</style>

{{-- ===== JAVASCRIPT ===== --}}
<script>
(function () {
    'use strict';

    // Field name sesuai schema calon_peserta (array key → kolom DB)
    var FIELDS = [
        'nama_lengkap',          // varchar(150) required
        'email',                 // varchar(191) nullable
        'no_hp',                 // varchar(30)  nullable
        'nomor_induk_pendidikan',// varchar(40)  nullable
        'institusi_asal',        // varchar(180) nullable
        'jurusan_prodi',         // varchar(150) nullable
    ];

    var tbody        = document.getElementById('tbody-calon');
    var tmpl         = document.getElementById('tmpl-baris-calon');
    var btnTambah    = document.getElementById('tombol-tambah-calon');
    var pesanMin     = document.getElementById('pesan-min-calon');
    var form         = document.getElementById('form-permohonan');

    // ── Hitung indeks tertinggi yang sudah ada ────────────────────────────
    function indeksTertinggi() {
        var baris = tbody.querySelectorAll('.baris-calon');
        var maks = baris.length - 1;
        baris.forEach(function (b) {
            var idx = parseInt(b.dataset.index, 10);
            if (!isNaN(idx) && idx > maks) maks = idx;
        });
        return maks;
    }

    // ── Perbarui nomor urut tampilan ──────────────────────────────────────
    function perbarui() {
        var baris = tbody.querySelectorAll('.baris-calon');
        baris.forEach(function (b, i) {
            var el = b.querySelector('.nomor-urut');
            if (el) el.textContent = i + 1;
        });
        // Sembunyikan pesan minimal jika sudah ada baris
        if (baris.length > 0 && pesanMin) pesanMin.style.display = 'none';
    }

    // ── Tambah baris baru ────────────────────────────────────────────────
    function tambahBaris() {
        var idx    = indeksTertinggi() + 1;
        var klon   = tmpl.content.cloneNode(true);
        var barisTr = klon.querySelector('tr');
        barisTr.dataset.index = idx;

        // Beri name yang benar pada setiap input sesuai urutan FIELDS
        var inputs = barisTr.querySelectorAll('input[type="text"], input[type="email"]');
        inputs.forEach(function (inp, i) {
            if (FIELDS[i]) inp.name = 'calon[' + idx + '][' + FIELDS[i] + ']';
        });

        // Pasang listener hapus
        var btnHapus = barisTr.querySelector('.btn-hapus-calon');
        if (btnHapus) btnHapus.addEventListener('click', hapusBaris);

        tbody.appendChild(klon);
        perbarui();

        // Fokus ke input nama pada baris baru
        var inputNama = tbody.querySelector('.baris-calon:last-child input[type="text"]');
        if (inputNama) inputNama.focus();
    }

    // ── Hapus baris ───────────────────────────────────────────────────────
    function hapusBaris() {
        var baris = tbody.querySelectorAll('.baris-calon');
        if (baris.length <= 1) {
            if (pesanMin) pesanMin.style.display = '';
            return; // jangan hapus baris terakhir
        }
        var barisTr = this.closest('.baris-calon');
        if (barisTr) {
            barisTr.remove();
            perbarui();
        }
    }

    // ── Pasang listener hapus pada baris yang dirender Blade ─────────────
    tbody.querySelectorAll('.btn-hapus-calon').forEach(function (btn) {
        btn.addEventListener('click', hapusBaris);
    });

    // ── Tombol tambah ────────────────────────────────────────────────────
    if (btnTambah) btnTambah.addEventListener('click', tambahBaris);

    // ── Validasi sebelum submit ───────────────────────────────────────────
    if (form) {
        form.addEventListener('submit', function (e) {
            var baris = tbody.querySelectorAll('.baris-calon');
            if (baris.length === 0) {
                e.preventDefault();
                if (pesanMin) pesanMin.style.display = '';
                tbody.closest('.kartu').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            // Pastikan minimal satu nama tidak kosong
            var adaNama = false;
            baris.forEach(function (b) {
                var inp = b.querySelector('input[name$="[nama_lengkap]"]');
                if (inp && inp.value.trim() !== '') adaNama = true;
            });
            if (!adaNama) {
                e.preventDefault();
                if (pesanMin) {
                    pesanMin.style.display = '';
                    pesanMin.querySelector('div').textContent = 'Nama lengkap minimal satu calon peserta wajib diisi.';
                }
                tbody.closest('.kartu').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    // ── Upload surat — tampilan nama file ─────────────────────────────────
    var inputSurat  = document.getElementById('input-surat');
    var zonaUnggah  = document.getElementById('zona-unggah');
    var labelFile   = document.getElementById('label-file');
    var infoFile    = document.getElementById('info-file');
    var namaFile    = document.getElementById('nama-file-dipilih');
    var ukuranFile  = document.getElementById('ukuran-file-dipilih');
    var BATAS_BYTE  = 5 * 1024 * 1024; // 5 MB

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    if (inputSurat) {
        inputSurat.addEventListener('change', function () {
            var file = this.files[0];
            if (!file) return;

            if (file.size > BATAS_BYTE) {
                labelFile.textContent = 'File terlalu besar! Maksimal 5 MB.';
                labelFile.style.color = 'var(--gagal, #dc2626)';
                zonaUnggah.classList.remove('aktif');
                infoFile.style.display = 'none';
                this.value = '';
                return;
            }

            labelFile.textContent = 'File dipilih';
            labelFile.style.color = '';
            zonaUnggah.classList.add('aktif');
            if (namaFile) namaFile.textContent = file.name;
            if (ukuranFile) ukuranFile.textContent = '(' + formatBytes(file.size) + ')';
            if (infoFile) infoFile.style.display = '';
        });

        // Drag & drop visual
        zonaUnggah.addEventListener('dragover', function (e) {
            e.preventDefault();
            zonaUnggah.classList.add('aktif');
        });
        zonaUnggah.addEventListener('dragleave', function () {
            zonaUnggah.classList.remove('aktif');
        });
        zonaUnggah.addEventListener('drop', function (e) {
            e.preventDefault();
            zonaUnggah.classList.remove('aktif');
            if (e.dataTransfer.files.length > 0) {
                inputSurat.files = e.dataTransfer.files;
                inputSurat.dispatchEvent(new Event('change'));
            }
        });
    }

    // Inisialisasi nomor urut
    perbarui();
})();
</script>

@endsection
