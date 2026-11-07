@extends('layouts.guest')
@section('judul', 'Login')
@section('konten')
<div class="login">
    <div class="login-hero">
        <img src="{{ asset('images/logo-unand.png') }}" alt="Lambang Universitas Andalas">
        <h2>Sistem Pengelolaan<br>Magang DTI</h2>
        <p>Direktorat Teknologi Informasi Universitas Andalas. Kelola pendaftaran, presensi, logbook, hingga sertifikat magang dalam satu tempat.</p>
        <span class="merek-sub">Universitas Andalas</span>
    </div>
    <div class="login-form">
        <h1>Selamat Datang</h1>
        <p class="redup">Masuk menggunakan akun Anda</p>
        @if ($errors->any())
            <div class="alert alert-gagal">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ url('/login') }}">
            @csrf
            <label>Email
                <input type="email" name="email" value="{{ old('email') }}" autofocus autocomplete="email">
            </label>
            <label>Password
                <input type="password" name="password" autocomplete="current-password">
            </label>
            <button class="btn">Login</button>
        </form>
        <p class="login-kaki">Belum punya akun? <a href="{{ route('informasi-program') }}">Lihat program magang yang dibuka</a></p>
    </div>
</div>
@endsection
