@extends('layouts.guest')

@section('judul', 'Perbaiki Permohonan '.$pengajuan->kode_pengajuan.' — DTI Universitas Andalas')
@section('kelas-body', 'guest-lp')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    <link rel="stylesheet" href="{{ asset('css/permohonan.css') }}">
    <link rel="stylesheet" href="{{ asset('css/penerimaan.css') }}">
@endpush

@section('nav-kanan')
    <a class="btn btn-garis" href="{{ route('publik.permohonan.status') }}">Kembali ke Status</a>
@endsection

@section('konten')
<div class="publik">
    <section class="publik-bagian publik-bagian--terang inst-terkirim">
        <div class="publik-wadah">
            <form class="inst-form" method="POST" action="{{ route('publik.permohonan.perbaikan.kirim') }}" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')

                <header class="inst-kartu inst-kepala">
                    <p class="publik-label">Perbaikan · {{ $pengajuan->kode_pengajuan }}</p>
                    <h2>Perbaiki Permohonan</h2>
                    <p>Ubah data sesuai catatan dari DTI, lalu kirim ulang. Nama institusi tidak dapat diubah.</p>
                    <p class="pn-kutip merah" style="margin-top:14px">{{ $pengajuan->catatan_perbaikan }}</p>
                </header>

                @if (session('gagal') || $errors->any())
                    <div class="alert alert-gagal inst-alert" role="alert">
                        <x-ikon nama="peringatan" />
                        <div>
                            <b>{{ session('gagal', \App\Services\PermohonanInstitusiService::PESAN_TIDAK_VALID) }}</b>
                            @if ($errors->any())<span class="kecil">Periksa kolom yang ditandai merah di bawah.</span>@endif
                        </div>
                    </div>
                @endif

                <fieldset class="inst-kartu">
                    <legend class="inst-judul-bagian"><span class="inst-no">1</span> Data institusi</legend>
                    <label>Nama institusi
                        <input value="{{ $pengajuan->nama_institusi }}" disabled>
                    </label>
                    <div class="dua-kolom">
                        <label>Nomor surat pengajuan <span class="wajib">*</span>
                            <input name="nomor_surat" value="{{ old('nomor_surat', $pengajuan->nomor_surat) }}" maxlength="100" @class(['salah' => $errors->has('nomor_surat')])>
                            @error('nomor_surat')<span class="pesan-salah">{{ $message }}</span>@enderror
                        </label>
                        <label>Tanggal surat <span class="wajib">*</span>
                            <input type="date" name="tanggal_surat" value="{{ old('tanggal_surat', $pengajuan->tanggal_surat?->toDateString()) }}" max="{{ today()->toDateString() }}" @class(['salah' => $errors->has('tanggal_surat')])>
                            @error('tanggal_surat')<span class="pesan-salah">{{ $message }}</span>@enderror
                        </label>
                    </div>
                </fieldset>

                <fieldset class="inst-kartu">
                    <legend class="inst-judul-bagian"><span class="inst-no">2</span> Narahubung institusi</legend>
                    <p class="inst-ket">Jika email diubah, gunakan email baru saat cek status berikutnya.</p>
                    <label>Nama narahubung <span class="wajib">*</span>
                        <input name="kontak_nama" value="{{ old('kontak_nama', $kontak['nama']) }}" maxlength="60" @class(['salah' => $errors->has('kontak_nama')])>
                        @error('kontak_nama')<span class="pesan-salah">{{ $message }}</span>@enderror
                    </label>
                    <div class="dua-kolom">
                        <label>Email <span class="wajib">*</span>
                            <input type="email" name="kontak_email" value="{{ old('kontak_email', $kontak['email']) }}" maxlength="60" @class(['salah' => $errors->has('kontak_email')])>
                            @error('kontak_email')<span class="pesan-salah">{{ $message }}</span>@enderror
                        </label>
                        <label>No. HP/WA <span class="wajib">*</span>
                            <input type="tel" inputmode="tel" name="kontak_telepon" value="{{ old('kontak_telepon', $kontak['telepon']) }}" maxlength="20" @class(['salah' => $errors->has('kontak_telepon')])>
                            @error('kontak_telepon')<span class="pesan-salah">{{ $message }}</span>@enderror
                        </label>
                    </div>
                </fieldset>

                <fieldset class="inst-kartu">
                    <legend class="inst-judul-bagian"><span class="inst-no">3</span> Data peserta</legend>
                    <p class="inst-ket">Ubah, tambah, atau hapus baris peserta sesuai surat terbaru.</p>
                    @include('permohonan._tabel-peserta', ['maksPeserta' => $maksPeserta, 'pesertaAwal' => $pesertaAwal])
                </fieldset>

                <fieldset class="inst-kartu">
                    <legend class="inst-judul-bagian"><span class="inst-no">4</span> Surat pengajuan</legend>
                    <label>Scan surat pengajuan {!! $suratWajib ? '<span class="wajib">*</span>' : '<small class="redup">(opsional — kosongkan jika surat tidak berubah)</small>' !!}
                        <input type="file" name="surat" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                               data-cek-berkas="info-surat" data-maks-kb="{{ $maksKb }}" @class(['salah' => $errors->has('surat')])>
                        <small class="petunjuk" id="info-surat">PDF, JPG, atau PNG · maksimal {{ $maksKb / 1024 }} MB</small>
                        @if ($suratWajib)
                            <span class="petunjuk">Surat sebelumnya ditandai <b>Tidak Sesuai</b>, jadi wajib diunggah ulang.</span>
                        @endif
                        @error('surat')<span class="pesan-salah">{{ $message }}</span>@enderror
                    </label>
                </fieldset>

                <div class="inst-kartu inst-kirim">
                    <div class="inst-kirim-aksi" style="margin-top:0">
                        <button class="btn">Kirim Perbaikan <x-ikon nama="panah-kanan" /></button>
                        <a class="btn btn-halus" href="{{ route('publik.permohonan.status') }}">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection
