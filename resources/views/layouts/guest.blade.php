<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="guest">
<header class="topbar">
    <a href="{{ route('informasi-program') }}" class="brand">DTI <b>Magang</b></a>
    <div class="topbar-kanan">
        @auth <a class="btn btn-garis" href="{{ route('dashboard') }}">Dashboard</a>
        @else <a class="btn btn-garis" href="{{ route('login') }}">Login</a> @endauth
    </div>
</header>
<main class="konten guest-konten">
    @foreach (['sukses', 'info'] as $kunci)
        @if (session($kunci))<div class="alert alert-{{ $kunci }}">{{ session($kunci) }}</div>@endif
    @endforeach
    @yield('konten')
</main>
</body>
</html>
