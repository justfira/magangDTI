@extends('layouts.app')
@section('judul', $judul)
@section('konten')
@php
    // Pisahkan kartu bernilai angka dan bernilai teks (mis. "Aktif", "Belum").
    $numerik = array_filter($kartu, 'is_numeric');
    $teks = array_diff_key($kartu, $numerik);

    // Kartu utama: statistik aksi pertama (menunggu/belum/siap); jika tidak ada, statistik pertama.
    $kunciUtama = null;
    $adaAksi = false;
    foreach ($numerik as $label => $nilai) {
        if (preg_match('/menunggu|belum|siap/i', $label)) {
            $kunciUtama = $label;
            $adaAksi = true;
            break;
        }
    }
    if ($kunciUtama === null && $numerik !== []) {
        $kunciUtama = array_key_first($numerik);
    }
    $sisa = $kunciUtama !== null ? array_diff_key($numerik, [$kunciUtama => true]) : $numerik;
@endphp
@php
    // Ikon & warna kartu statistik ditebak dari labelnya.
    $gayaStat = function (string $label, int $i): array {
        $l = mb_strtolower($label);
        $ikon = match (true) {
            str_contains($l, 'pengajuan') || str_contains($l, 'keputusan') => 'papan-centang',
            str_contains($l, 'program') => 'koper',
            str_contains($l, 'sertifikat') => 'medali',
            str_contains($l, 'logbook') => 'buku',
            str_contains($l, 'tugas') || str_contains($l, 'progres') => 'tugas',
            str_contains($l, 'izin') || str_contains($l, 'hadir') || str_contains($l, 'presensi') => 'jam',
            str_contains($l, 'ditempatkan') => 'lokasi',
            str_contains($l, 'peserta') || str_contains($l, 'calon') => 'pengguna',
            default => 'grafik',
        };
        $varian = ['', 'stat--kuning', 'stat--peru', 'stat--olive'][$i % 4];
        return [$ikon, $varian];
    };
    $namaDepan = \Illuminate\Support\Str::of(auth()->user()->nama_lengkap ?? '')->explode(' ')->first();
    $jam = (int) now()->format('G');
    $salam = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
@endphp

<section class="sambutan">
    <div class="sambutan-teks">
        <p class="kicker">{{ $judul }}</p>
        <h1>{{ $salam }}, {{ $namaDepan }}</h1>
        <p>Berikut ringkasan aktivitas dan statistik terbaru Sistem Pengelolaan Magang DTI.</p>
        <div class="sambutan-meta">
            <span><x-ikon nama="perisai" />{{ auth()->user()->peran->nama_peran }}</span>
            <span><x-ikon nama="kalender" />{{ now()->translatedFormat('l, d F Y') }}</span>
        </div>
    </div>
    @if ($kunciUtama !== null)
        <div class="sambutan-sorot">
            <div class="label-kecil">{{ $adaAksi ? 'Perlu tindakan' : 'Prioritas utama' }}</div>
            <div class="angka-utama">{{ $numerik[$kunciUtama] }}</div>
            <div class="ket">{{ $kunciUtama }}</div>
        </div>
    @endif
</section>

@if ($teks)
    <div class="baris-status">
        @foreach ($teks as $label => $nilai)
            @php
                $kelasStatus = match (true) {
                    in_array($nilai, ['Aktif', 'Sudah', 'Selesai']) => 'status-sukses',
                    in_array($nilai, ['Belum', 'Menunggu']) => 'status-peringatan',
                    default => 'status-netral',
                };
            @endphp
            <span class="status {{ $kelasStatus }}">{{ $label }}: {{ $nilai }}</span>
        @endforeach
    </div>
@endif

@if ($sisa)
    <div class="statistik-grid">
        @foreach (array_keys($sisa) as $i => $label)
            @php [$ikon, $varian] = $gayaStat($label, $i); @endphp
            <div class="stat {{ $varian }}">
                <span class="ikon-bulat"><x-ikon :nama="$ikon" /></span>
                <div class="nilai">{{ $sisa[$label] }}</div>
                <div class="label">{{ $label }}</div>
            </div>
        @endforeach
    </div>
@endif

@isset($pengajuan)
    <div class="kartu">
        <div class="kartu-kepala">
            <div class="kartu-judul">
                <span class="ikon-bulat"><x-ikon nama="papan-centang" /></span>
                <div>
                    <h2>Pengajuan Saya</h2>
                    <p>Pantau status setiap pengajuan magang yang pernah Anda kirim.</p>
                </div>
            </div>
            <a class="btn btn-garis btn-kecil" href="{{ route('informasi-program') }}"><x-ikon nama="koper" /> Program dibuka</a>
        </div>
        @forelse ($pengajuan as $p)
            @php
                $kelasStatus = match ($p->status_pengajuan) {
                    'Diajukan' => 'status-peringatan',
                    'Lengkap'  => 'status-sukses',
                    'Perlu Perbaikan' => 'status-gagal',
                    default => 'status-netral',
                };
            @endphp
            <div class="notif">
                <div class="notif-isi">
                    <span class="inisial v{{ $loop->index % 5 }}"><x-ikon nama="berkas" /></span>
                    <div>
                        <b>{{ $p->program?->nama_program ?? 'Jalur Institusi' }}</b>
                        <small class="redup">Kode {{ $p->kode_pengajuan }}</small>
                    </div>
                </div>
                <span class="status {{ $kelasStatus }}">{{ $p->status_pengajuan }}</span>
            </div>
        @empty
            <div class="kosong">
                <div class="ilustrasi"><x-ikon nama="kotak-masuk" /></div>
                <b>Belum ada pengajuan</b>
                Lihat program magang yang sedang dibuka untuk mulai mendaftar.
                <br><a class="btn" href="{{ route('informasi-program') }}">Lihat Program Magang <x-ikon nama="panah-kanan" /></a>
            </div>
        @endforelse
    </div>
@endisset
@endsection
