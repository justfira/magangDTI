@extends('layouts.app')
@section('judul', 'Tambah Permohonan')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/permohonan.css') }}">
@endpush
@section('konten')
<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('superadmin.permohonan.index') }}"><x-ikon nama="kembali" /> Kembali ke daftar permohonan</a>
        <h1>Tambah Permohonan Institusi</h1>
        <p class="sub-judul">Salin data dari surat pengajuan yang diterima. Permohonan tersimpan dengan status awal <b>Diajukan</b>. Kolom bertanda <span class="wajib">*</span> wajib diisi.</p>
    </div>
</div>

<form class="kartu form form-kartu" method="POST" action="{{ route('superadmin.permohonan.store') }}" enctype="multipart/form-data" novalidate>
    @csrf

    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat"><x-ikon nama="gedung" /></span> Data institusi</h2>
            <p>Identitas institusi pengirim dan surat pengajuannya.</p>
        </div>
        <div class="form-bagian-isi">
            <label>Nama institusi <span class="wajib">*</span>
                <input name="nama_institusi" value="{{ old('nama_institusi') }}" maxlength="180" placeholder="mis. Politeknik Negeri Padang" @class(['salah' => $errors->has('nama_institusi')])>
                @error('nama_institusi')<span class="pesan-salah">{{ $message }}</span>@enderror
            </label>
            <div class="dua-kolom">
                <label>Nomor surat <span class="wajib">*</span>
                    <input name="nomor_surat" value="{{ old('nomor_surat') }}" maxlength="100" placeholder="mis. 123/PL9/KM/2026" @class(['salah' => $errors->has('nomor_surat')])>
                    @error('nomor_surat')<span class="pesan-salah">{{ $message }}</span>@enderror
                </label>
                <label>Tanggal surat <span class="wajib">*</span>
                    <input type="date" name="tanggal_surat" value="{{ old('tanggal_surat') }}" max="{{ today()->toDateString() }}" @class(['salah' => $errors->has('tanggal_surat')])>
                    @error('tanggal_surat')<span class="pesan-salah">{{ $message }}</span>@enderror
                </label>
            </div>
        </div>
    </div>

    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat kuning"><x-ikon nama="surel" /></span> Kontak institusi</h2>
            <p>Narahubung yang bisa dihubungi DTI terkait permohonan ini (mis. dosen pembimbing / bagian akademik).</p>
        </div>
        <div class="form-bagian-isi">
            <label>Nama narahubung <span class="wajib">*</span>
                <input name="kontak_nama" value="{{ old('kontak_nama') }}" maxlength="60" placeholder="mis. Dr. Hendra, M.Kom." @class(['salah' => $errors->has('kontak_nama')])>
                @error('kontak_nama')<span class="pesan-salah">{{ $message }}</span>@enderror
            </label>
            <div class="dua-kolom">
                <label>Email <span class="wajib">*</span>
                    <input type="email" name="kontak_email" value="{{ old('kontak_email') }}" maxlength="60" placeholder="akademik@institusi.ac.id" @class(['salah' => $errors->has('kontak_email')])>
                    @error('kontak_email')<span class="pesan-salah">{{ $message }}</span>@enderror
                </label>
                <label>No. HP/WA <span class="wajib">*</span>
                    <input type="tel" inputmode="tel" name="kontak_telepon" value="{{ old('kontak_telepon') }}" maxlength="20" placeholder="08xxxxxxxxxx" @class(['salah' => $errors->has('kontak_telepon')])>
                    @error('kontak_telepon')<span class="pesan-salah">{{ $message }}</span>@enderror
                </label>
            </div>
        </div>
    </div>

    <div class="form-bagian form-bagian--penuh">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat olive"><x-ikon nama="pengguna" /></span> Data peserta</h2>
            <p>Satu baris untuk satu peserta yang diajukan dalam surat. Klik <b>Tambah peserta</b> untuk menambah baris (maks. {{ $maksPeserta }}).</p>
        </div>
        <div class="form-bagian-isi">
            @include('permohonan._tabel-peserta', ['maksPeserta' => $maksPeserta])
        </div>
    </div>

    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat peru"><x-ikon nama="berkas" /></span> Surat pengajuan</h2>
            <p>Scan/foto surat resmi dari institusi. Format PDF, JPG, atau PNG, maksimal {{ $maksKb / 1024 }} MB.</p>
        </div>
        <div class="form-bagian-isi">
            <label>Scan surat pengajuan <span class="wajib">*</span>
                <input type="file" name="surat" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                       data-cek-berkas="info-surat" data-maks-kb="{{ $maksKb }}" @class(['salah' => $errors->has('surat')])>
                <small class="petunjuk" id="info-surat">PDF/JPG/PNG · maks. {{ $maksKb / 1024 }} MB</small>
                @error('surat')<span class="pesan-salah">{{ $message }}</span>@enderror
                @if ($errors->any() && ! $errors->has('surat'))
                    <span class="petunjuk">Demi keamanan, berkas perlu dipilih ulang setelah ada kolom yang salah.</span>
                @endif
            </label>
        </div>
    </div>

    <div class="form-kaki">
        <a class="btn btn-halus" href="{{ route('superadmin.permohonan.index') }}">Batal</a>
        <button class="btn"><x-ikon nama="centang-saja" /> Simpan</button>
    </div>
</form>
@endsection
