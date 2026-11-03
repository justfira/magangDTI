@extends('layouts.guest')
@section('judul', 'Login')
@section('konten')
<div class="kartu login">
    <h1>Selamat Datang</h1>
    <p class="redup">Sistem Pengelolaan Magang DTI Universitas Andalas</p>
    @if ($errors->any())
        <div class="alert alert-gagal">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ url('/login') }}">
        @csrf
        <label>Email <input type="email" name="email" value="{{ old('email') }}" autofocus></label>
        <label>Password <input type="password" name="password"></label>
        <button class="btn">Login</button>
    </form>
</div>
@endsection
