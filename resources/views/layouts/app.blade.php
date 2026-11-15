<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Dashboard') — {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-unand.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php
/** Ikon menu (SVG stroke 24×24), dipetakan dari label di config/menu.php. */
$ikonMenu = [
    'Dashboard'                => 'M4 13h6V4H4zM14 20h6v-9h-6zM4 20h6v-4H4zM14 8h6V4h-6z',
    'Program Magang'           => 'M4 8h16v11H4zM9 8V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M4 13h16',
    'Permohonan Institusi'     => 'M5 21V4h9v17M8 8h.01M11 8h.01M8 12h.01M11 12h.01M14 10h5v11M17 14h.01M17 17h.01M3 21h18',
    'Verifikasi Pengajuan'     => 'M9 4h6v3H9zM9 5H7a1 1 0 0 0-1 1v13a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2M9 13l2 2 4-4',
    'Manajemen Akun'           => 'M8 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM2.5 20v-1.5A4.5 4.5 0 0 1 7 14h2a4.5 4.5 0 0 1 4.5 4.5V20M16 14.5a3 3 0 1 0 0-6M18.5 14h.5A3.5 3.5 0 0 1 22 17.5V20',
    'Aktivasi Peserta'         => 'M8 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM2.5 20v-1.5A4.5 4.5 0 0 1 7 14h2a4.5 4.5 0 0 1 4.5 4.5V20M14.5 13l2 2 4-4',
    'Kelola Presensi'          => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3.5 2',
    'Sertifikat'               => 'M12 14a5.5 5.5 0 1 0 0-11 5.5 5.5 0 0 0 0 11zM8.5 12.5 7 21l5-2.8L17 21l-1.5-8.5',
    'Monitoring'               => 'M5 20v-6M12 20V6M19 20v-9M3.5 20h17',
    'Rekomendasi Penerimaan'   => 'M7 11v10M13.5 3.5 12 10h6.5a1.5 1.5 0 0 1 1.5 1.8l-1.3 5.7a2 2 0 0 1-2 1.5H7M7 19H3v-8h4',
    'Penetapan Seksi & Mentor' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 16.5a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9zM12 13.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z',
    'Penyelesaian Magang'      => 'M5 21V3M5 4h12l-3 4 3 4H5',
    'Persetujuan Penerimaan'   => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM8.5 12.5l2.5 2.5 5-5.5',
    'Persetujuan Penyelesaian' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM8.5 12.5l2.5 2.5 5-5.5',
    'Peserta Bimbingan'        => 'M8 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM2.5 20v-1.5A4.5 4.5 0 0 1 7 14h2a4.5 4.5 0 0 1 4.5 4.5V20M16 14.5a3 3 0 1 0 0-6M18.5 14h.5A3.5 3.5 0 0 1 22 17.5V20',
    'Tugas'                    => 'M9 4h6v3H9zM9 5H7a1 1 0 0 0-1 1v13a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2M9 11h6M9 15h4',
    'Verifikasi Izin & Susulan'=> 'M14 3H7a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V7zM14 3v4h4M9 12h6M9 16h6',
    'Periksa Logbook'          => 'M12 6.5C10.5 5 8.5 4.5 6 4.5H3v13h3c2.5 0 4.5.5 6 2 1.5-1.5 3.5-2 6-2h3v-13h-3c-2.5 0-4.5.5-6 2zM12 6.5v13',
    'Evaluasi'                 => 'M12 3.5l2.7 5.4 6 1-4.4 4.2 1.1 5.9-5.4-2.8-5.4 2.8 1.1-5.9L3.3 9.9l6-1z',
    'Penempatan'               => 'M12 21s-7-5.5-7-11a7 7 0 0 1 14 0c0 5.5-7 11-7 11zM12 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
    'Presensi'                 => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3.5 2',
    'Izin & Presensi Susulan'  => 'M14 3H7a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V7zM14 3v4h4M9 12h6M9 16h6',
    'Logbook'                  => 'M12 6.5C10.5 5 8.5 4.5 6 4.5H3v13h3c2.5 0 4.5.5 6 2 1.5-1.5 3.5-2 6-2h3v-13h-3c-2.5 0-4.5.5-6 2zM12 6.5v13',
    'Status Pendaftaran'       => 'M9 4h6v3H9zM9 5H7a1 1 0 0 0-1 1v13a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2M9 13l2 2 4-4',
];
@endphp
<div class="tirai" onclick="document.body.classList.remove('menu-buka')"></div>

<aside class="sidebar" id="menu-samping">
    <a href="{{ route('dashboard') }}" class="merek">
        <img src="{{ asset('images/logo-unand.png') }}" alt="Lambang Universitas Andalas">
        <span>
            <span class="merek-nama">DTI <b>Magang</b></span>
            <span class="merek-sub">Universitas Andalas</span>
        </span>
    </a>
    <nav class="menu">
        @foreach ($menu as $item)
            @if (\Illuminate\Support\Facades\Route::has($item['route']))
                <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['route'].'*') ? 'aktif' : '' }}">
                    <svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="{{ $ikonMenu[$item['label']] ?? 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z' }}"/>
                    </svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>
    <div class="sidebar-kaki">Sistem Pengelolaan Magang<br>Direktorat Teknologi Informasi</div>
</aside>

<div class="kolom-utama">
    <header class="topbar">
        <button class="burger" onclick="document.body.classList.toggle('menu-buka')" aria-label="Menu" aria-controls="menu-samping">
            <svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <p class="breadcrumb"><span class="asal">Beranda</span><span class="pemisah">/</span><b>@yield('judul', 'Dashboard')</b></p>
        <div class="topbar-kanan">
            <a href="{{ route('notifikasi.index') }}" class="lonceng" title="Notifikasi">
                <svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M10.3 21a2 2 0 0 0 3.4 0"/></svg>
                @if($jumlahNotifikasi)<span class="badge">{{ $jumlahNotifikasi }}</span>@endif
            </a>
            <details class="profil">
                <summary>
                    <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->nama_lengkap ?? '?', 0, 1)) }}</span>
                    <span class="nama">{{ auth()->user()->nama_lengkap }} <small>({{ auth()->user()->peran->nama_peran }})</small></span>
                </summary>
                <div class="profil-menu">
                    <p class="profil-nama">{{ auth()->user()->nama_lengkap }}</p>
                    <p class="redup peran">{{ auth()->user()->peran->nama_peran }}</p>
                    <hr>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="btn btn-bahaya">Keluar</button>
                    </form>
                </div>
            </details>
        </div>
    </header>
    <main class="konten">
        @foreach (['sukses' => 'sukses', 'info' => 'info'] as $kunci => $kelas)
            @if (session($kunci))<div class="alert alert-{{ $kelas }}">{{ session($kunci) }}</div>@endif
        @endforeach
        @if ($errors->any())
            <div class="alert alert-gagal"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        @yield('konten')
    </main>
    <footer class="footer">
        <span>© {{ date('Y') }} <b>Direktorat Teknologi Informasi Universitas Andalas</b></span>
        <span>Sistem Pengelolaan Magang</span>
    </footer>
</div>

<script>
// Tutup dropdown profil bila klik di luar (tanpa framework).
document.addEventListener('click', function (e) {
    document.querySelectorAll('.profil[open]').forEach(function (p) {
        if (! p.contains(e.target)) { p.removeAttribute('open'); }
    });
});
</script>
</body>
</html>
