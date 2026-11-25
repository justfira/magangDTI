@extends('layouts.app')
@section('judul', 'Verifikasi Izin & Presensi Susulan')
@section('konten')
<link rel="stylesheet" href="{{ asset('css/presensi.css') }}">

<h1>Verifikasi Izin &amp; Presensi Susulan</h1>

@php
    $tautan = fn (?string $s, ?string $j) => route('mentor.kehadiran.index', array_filter(['status' => $s, 'jenis' => $j]));
    $total = $jumlah->sum();
@endphp
<nav class="pr-tab" aria-label="Filter status">
    <a href="{{ $tautan(null, $jenis) }}" class="{{ $status ? '' : 'aktif' }}">Semua ({{ $total }})</a>
    @foreach (\App\Models\PengajuanKehadiran::STATUS as $s)
        <a href="{{ $tautan($s, $jenis) }}" class="{{ $status === $s ? 'aktif' : '' }}">{{ $s }} ({{ $jumlah[$s] ?? 0 }})</a>
    @endforeach
</nav>
<nav class="pr-tab" aria-label="Filter jenis">
    <a href="{{ $tautan($status, null) }}" class="{{ $jenis ? '' : 'aktif' }}">Semua jenis</a>
    <a href="{{ $tautan($status, 'SUSULAN') }}" class="{{ $jenis === 'SUSULAN' ? 'aktif' : '' }}">Presensi susulan</a>
    <a href="{{ $tautan($status, 'IZIN') }}" class="{{ $jenis === 'IZIN' ? 'aktif' : '' }}">Izin</a>
</nav>

@forelse ($pengajuan as $p)
    @php $garis = ['Menunggu Verifikasi' => 'pr-garis-tunggu', 'Disetujui' => 'pr-garis-sukses', 'Ditolak' => 'pr-garis-gagal'][$p->status] ?? ''; @endphp
    <article class="kartu pr-pengajuan {{ $garis }}">
        <div class="pr-kepala">
            <h2>{{ $p->pesertaMagang->pengguna->nama_lengkap }}</h2>
            <div>
                @include('peserta.presensi._badge', ['status' => $p->jenis === 'IZIN' ? 'Izin' : 'SUSULAN',
                    'label' => $p->jenis === 'IZIN' ? 'Izin ('.$p->jenis_izin.')' : 'Presensi susulan'])
                @include('peserta.presensi._badge', ['status' => $p->status])
            </div>
        </div>
        <dl class="pr-meta">
            <dt>Tanggal kegiatan</dt><dd>{{ $p->tanggal_kegiatan->translatedFormat('l, d F Y') }}</dd>
            <dt>Diajukan</dt><dd>{{ $p->waktu_pengajuan->translatedFormat('d M Y, H:i') }}</dd>
            <dt>Alasan</dt><dd>{{ $p->alasan }}</dd>
            @if ($p->keterangan)<dt>Keterangan</dt><dd>{{ $p->keterangan }}</dd>@endif
            @if ($p->jenis === 'IZIN')
                <dt>Bukti pendukung</dt>
                <dd>@if ($p->path_lampiran)
                        <a href="{{ route('mentor.kehadiran.lampiran', $p) }}" target="_blank" rel="noopener">Lihat lampiran</a>
                    @else <span class="redup">Tidak ada (opsional)</span> @endif</dd>
            @else
                <dt>Lokasi pengajuan</dt>
                <dd>{{ $p->latitude }}, {{ $p->longitude }} <span class="redup">(akurasi ± {{ round($p->akurasi_meter) }} m)</span></dd>
            @endif
            @if ($p->catatan_keputusan)<dt>Catatan keputusan</dt><dd>{{ $p->catatan_keputusan }}</dd>@endif
        </dl>

        @if ($p->status === 'Menunggu Verifikasi')
            <div class="pr-keputusan">
                <form class="form" method="POST" action="{{ route('mentor.kehadiran.setujui', $p) }}">@csrf
                    <label>Catatan (opsional) <textarea name="catatan_keputusan"></textarea></label>
                    <button class="btn">Setujui</button>
                </form>
                <form class="form" method="POST" action="{{ route('mentor.kehadiran.tolak', $p) }}">@csrf
                    <label>Alasan penolakan (wajib) <textarea name="catatan_keputusan"></textarea></label>
                    <button class="btn btn-garis">Tolak</button>
                </form>
            </div>
        @endif
    </article>
@empty
    <div class="kartu"><p class="redup">Tidak ada pengajuan untuk filter ini.</p></div>
@endforelse
{{ $pengajuan->links() }}
@endsection
