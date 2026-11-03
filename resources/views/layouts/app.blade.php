<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Dashboard') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="topbar">
    <button class="burger" onclick="document.body.classList.toggle('menu-buka')" aria-label="Menu">☰</button>
    <a href="{{ route('dashboard') }}" class="brand">DTI <b>Magang</b></a>
    <div class="topbar-kanan">
        <a href="{{ route('notifikasi.index') }}" class="lonceng" title="Notifikasi">🔔@if($jumlahNotifikasi)<span class="badge">{{ $jumlahNotifikasi }}</span>@endif</a>
        <span class="nama">{{ auth()->user()->nama_lengkap }} <small>({{ auth()->user()->peran->nama_peran }})</small></span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-garis">Logout</button></form>
    </div>
</header>
<div class="wadah">
    <nav class="sidebar">
        @foreach ($menu as $item)
            @if (\Illuminate\Support\Facades\Route::has($item['route']))
                <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['route'].'*') ? 'aktif' : '' }}">{{ $item['label'] }}</a>
            @endif
        @endforeach
    </nav>
    <main class="konten">
        @foreach (['sukses' => 'sukses', 'info' => 'info'] as $kunci => $kelas)
            @if (session($kunci))<div class="alert alert-{{ $kelas }}">{{ session($kunci) }}</div>@endif
        @endforeach
        @if ($errors->any())
            <div class="alert alert-gagal"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        @yield('konten')
    </main>
</div>
</body>
</html>
