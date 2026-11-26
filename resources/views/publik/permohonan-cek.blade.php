@extends('layouts.guest')

@section('judul', 'Cek Status Permohonan Institusi — DTI Universitas Andalas')
@section('kelas-body', 'guest-lp')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    <link rel="stylesheet" href="{{ asset('css/permohonan.css') }}">
@endpush

@section('nav-kanan')
    <a class="tautan" href="{{ route('publik.permohonan.create') }}">Jalur Institusi</a>
    <a class="btn btn-garis" href="{{ route('publik.permohonan.create') }}#formulir">Ajukan Permohonan</a>
@endsection

@section('konten')
<div class="publik">
    <section class="publik-bagian publik-bagian--terang inst-terkirim">
        <div class="publik-wadah">
            <form class="inst-kartu inst-cek" method="POST" action="{{ route('publik.permohonan.masuk') }}" novalidate>
                @csrf
                <span class="inst-sukses-ikon"><x-ikon nama="cari" /></span>
                <p class="publik-label">Jalur Institusi</p>
                <h1>Cek status permohonan</h1>
                <p class="inst-ket">Masukkan kode permohonan yang Anda terima setelah mengirim formulir, beserta email narahubung yang diisi pada formulir.</p>

                <label>Kode permohonan
                    <input name="kode" value="{{ old('kode', $kodeAwal) }}" maxlength="30" placeholder="mis. INS-2026-0001" autocomplete="off"
                           autocapitalize="characters" spellcheck="false" class="inst-input-kode @error('kode') salah @enderror">
                    @error('kode')<span class="pesan-salah">{{ $message }}</span>@enderror
                </label>
                <label>Email narahubung
                    <input type="email" name="email" value="{{ old('email') }}" maxlength="60" placeholder="akademik@institusi.ac.id" autocomplete="email" @class(['salah' => $errors->has('email')])>
                    @error('email')<span class="pesan-salah">{{ $message }}</span>@enderror
                </label>

                <div class="inst-kirim-aksi">
                    <button class="btn">Cek Status <x-ikon nama="panah-kanan" /></button>
                </div>
                <p class="petunjuk inst-cek-bantuan">Lupa kode? Hubungi Direktorat Teknologi Informasi Universitas Andalas dengan menyebutkan nama institusi dan nomor surat.</p>
            </form>
        </div>
    </section>
</div>
@endsection
