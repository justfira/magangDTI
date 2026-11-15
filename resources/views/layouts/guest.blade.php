<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul') — {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-unand.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('css')
</head>
<body class="guest @yield('kelas-body')">
<div class="kolom-utama">
    <header class="topbar">
        <a href="{{ route('informasi-program') }}" class="brand">
            <img src="{{ asset('images/logo-unand.png') }}" alt="Lambang Universitas Andalas">
            <span>DTI <b>Magang</b></span>
        </a>
        <div class="topbar-kanan">
            @hasSection('nav-kanan')
                @yield('nav-kanan')
            @else
                @auth
                    <a class="tautan" href="{{ route('informasi-program') }}">Program Magang</a>
                    <a class="btn btn-garis" href="{{ route('dashboard') }}">Dashboard</a>
                @else
                    <a class="tautan" href="{{ route('informasi-program') }}">Program Magang</a>
                    <a class="btn" href="{{ route('login') }}">Login</a>
                @endauth
            @endif
        </div>
    </header>
    <main class="konten guest-konten">
        @foreach (['sukses', 'info'] as $kunci)
            @if (session($kunci))<div class="alert alert-{{ $kunci }}">{{ session($kunci) }}</div>@endif
        @endforeach
        @yield('konten')
    </main>
    <footer class="footer">
        @yield('footer-extra')
        <div class="footer-legal">
            <span>© {{ date('Y') }} <b>Direktorat Teknologi Informasi Universitas Andalas</b></span>
            <span>Sistem Pengelolaan Magang</span>
        </div>
    </footer>
</div>
</body>
</html>
