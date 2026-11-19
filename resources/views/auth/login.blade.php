@extends('layouts.guest')
@section('judul', 'Login')
@section('kelas-body', 'guest-masuk')
@section('konten')
<div class="login">
    <div class="login-hero">
        <div class="login-hero-logo">
            <img src="{{ asset('images/logo-unand.png') }}" alt="Lambang Universitas Andalas">
            <span>Universitas Andalas<b>DTI Magang</b></span>
        </div>
        <h2>Satu tempat untuk seluruh perjalanan <em>magangmu</em>.</h2>
        <p>Direktorat Teknologi Informasi Universitas Andalas. Kelola pendaftaran, presensi, logbook, hingga sertifikat magang dalam satu sistem.</p>
        <ul class="login-fitur">
            <li><x-ikon nama="papan-centang" /> Pendaftaran online</li>
            <li><x-ikon nama="jam" /> Presensi harian</li>
            <li><x-ikon nama="buku" /> Logbook &amp; tugas</li>
            <li><x-ikon nama="medali" /> Sertifikat digital</li>
        </ul>
    </div>
    <div class="login-form">
        <span class="salam-ikon"><x-ikon nama="kunci" /></span>
        <h1>Selamat Datang</h1>
        <p class="redup">Masuk menggunakan akun Anda untuk melanjutkan.</p>
        @if ($errors->any())
            <div class="alert alert-gagal"><x-ikon nama="peringatan" /><div>{{ $errors->first() }}</div></div>
        @endif
        <form method="POST" action="{{ url('/login') }}">
            @csrf
            <label>Email
                <span class="input-ikon">
                    <x-ikon nama="surel" />
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@contoh.ac.id" autofocus autocomplete="email">
                </span>
            </label>
            <label>Password
                <span class="input-ikon">
                    <x-ikon nama="kunci" />
                    <input type="password" name="password" id="kata-sandi" placeholder="Masukkan password" autocomplete="current-password">
                    <button type="button" class="intip" aria-label="Tampilkan password" onclick="var i=document.getElementById('kata-sandi');i.type=i.type==='password'?'text':'password';this.setAttribute('aria-pressed',i.type==='text')"><x-ikon nama="mata" /></button>
                </span>
            </label>
            <button class="btn">Login <x-ikon nama="panah-kanan" /></button>
        </form>
        <p class="login-kaki">Belum punya akun? <a href="{{ route('informasi-program') }}">Lihat program magang yang dibuka</a></p>
    </div>
</div>
@endsection
