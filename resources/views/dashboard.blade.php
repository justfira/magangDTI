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
<div class="kepala-halaman">
    <h1>{{ $judul }}</h1>
    <p class="sub-judul">{{ now()->translatedFormat('l, d F Y') }} · ringkasan aktivitas dan statistik terbaru</p>
</div>

@if ($kunciUtama !== null)
    <div class="tindakan">
        <div class="utama">
            <span class="angka-utama">{{ $numerik[$kunciUtama] }}</span>
            <div>
                <div class="kicker">{{ $adaAksi ? 'Perlu tindakan' : 'Prioritas utama' }}</div>
                <div class="ket">{{ $kunciUtama }}</div>
            </div>
        </div>
    </div>
@endif

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
    <div class="strip">
        @foreach ($sisa as $label => $nilai)
            <div><div class="l">{{ $label }}</div><div class="n">{{ $nilai }}</div></div>
        @endforeach
    </div>
@endif

@isset($pengajuan)
    <div class="kartu">
        <div class="kartu-kepala">
            <h2>Pengajuan Saya</h2>
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
                <div>
                    <b>{{ $p->kode_pengajuan }}</b> — {{ $p->program?->nama_program ?? 'Jalur Institusi' }}<br>
                    <small class="redup">Status: {{ $p->status_pengajuan }}</small>
                </div>
                <span class="status {{ $kelasStatus }}">{{ $p->status_pengajuan }}</span>
            </div>
        @empty
            <div class="kosong">
                <b>Belum ada pengajuan</b>
                Lihat program magang yang sedang dibuka untuk mulai mendaftar.
                <br><a class="btn" href="{{ route('informasi-program') }}">Lihat Program Magang</a>
            </div>
        @endforelse
    </div>
@endisset
@endsection
