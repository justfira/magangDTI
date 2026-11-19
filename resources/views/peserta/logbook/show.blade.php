@extends('layouts.app')
@section('judul', 'Detail Logbook')
@section('konten')
@php
    $statusKelas = ['Dikirim' => 'status-peringatan', 'Disetujui' => 'status-sukses', 'Perlu Perbaikan' => 'status-gagal'];
@endphp
<div class="judul-aksi">
    <h1>Detail Logbook</h1>
    <span class="status {{ $statusKelas[$logbook->status] ?? 'status-netral' }}">{{ $logbook->status }}</span>
</div>

@if ($logbook->status === 'Perlu Perbaikan' && $logbook->catatan_mentor)
<div class="alert alert-info"><b>Catatan mentor:</b> {{ $logbook->catatan_mentor }}</div>
@endif

<div class="kartu">
    <table>
        <tr><th style="width:180px">Tanggal kegiatan</th><td>{{ $logbook->tanggal_kegiatan->translatedFormat('l, d M Y') }}</td></tr>
        <tr><th>Waktu kirim</th><td>{{ $logbook->waktu_kirim?->translatedFormat('d M Y, H:i') ?? '—' }}</td></tr>
        <tr><th>Status pemeriksaan</th><td><span class="status {{ $statusKelas[$logbook->status] ?? 'status-netral' }}">{{ $logbook->status }}</span></td></tr>
        <tr><th>Waktu diperiksa</th><td>{{ $logbook->waktu_periksa?->translatedFormat('d M Y, H:i') ?? 'Belum diperiksa' }}</td></tr>
        <tr><th>Catatan mentor</th><td>{{ $logbook->catatan_mentor ?? '—' }}</td></tr>
    </table>
</div>

<div class="kartu">
    <h2>Uraian aktivitas</h2>
    <p style="white-space:pre-line">{{ $logbook->uraian_aktivitas }}</p>
</div>

<div class="judul-aksi">
    @if ($logbook->status === 'Perlu Perbaikan')
        <a class="btn" href="{{ route('peserta.logbook.edit', $logbook) }}">Perbaiki Logbook</a>
    @endif
    <a class="btn btn-garis" href="{{ route('peserta.logbook.index') }}">Kembali ke Daftar</a>
</div>
@endsection
