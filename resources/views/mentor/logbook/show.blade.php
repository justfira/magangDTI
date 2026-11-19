@extends('layouts.app')
@section('judul', 'Tinjau Logbook')
@section('konten')
@php
    $statusKelas = ['Dikirim' => 'status-peringatan', 'Disetujui' => 'status-sukses', 'Perlu Perbaikan' => 'status-gagal'];
    $peserta = $logbook->pesertaMagang;
@endphp
<div class="judul-aksi">
    <h1>Tinjau Logbook</h1>
    <span class="status {{ $statusKelas[$logbook->status] ?? 'status-netral' }}">{{ $logbook->status }}</span>
</div>

<div class="kartu">
    <table>
        <tr><th style="width:180px">Peserta</th><td>{{ $peserta->pengguna->nama_lengkap }}</td></tr>
        <tr><th>Tanggal kegiatan</th><td>{{ $logbook->tanggal_kegiatan->translatedFormat('l, d M Y') }}</td></tr>
        <tr><th>Waktu kirim</th><td>{{ $logbook->waktu_kirim?->translatedFormat('d M Y, H:i') ?? '—' }}</td></tr>
        @if ($logbook->waktu_periksa)
            <tr><th>Waktu diperiksa</th><td>{{ $logbook->waktu_periksa->translatedFormat('d M Y, H:i') }}</td></tr>
            <tr><th>Catatan pemeriksaan</th><td>{{ $logbook->catatan_mentor ?? '—' }}</td></tr>
        @endif
    </table>
</div>

<div class="kartu">
    <h2>Uraian aktivitas</h2>
    <p style="white-space:pre-line">{{ $logbook->uraian_aktivitas }}</p>
</div>

@if ($logbook->status === 'Dikirim')
<div class="kartu form">
    <h2>Keputusan pemeriksaan</h2>
    <form method="POST" action="{{ route('mentor.logbook.periksa', $logbook) }}">
        @csrf
        <label>Keputusan
            <select name="keputusan">
                <option value="Disetujui" @selected(old('keputusan') === 'Disetujui')>Disetujui</option>
                <option value="Perlu Perbaikan" @selected(old('keputusan') === 'Perlu Perbaikan')>Perlu Perbaikan</option>
            </select>
        </label>
        <label>Catatan untuk peserta <span class="redup">(wajib apabila Perlu Perbaikan)</span>
            <textarea name="catatan_mentor" rows="4" placeholder="Umpan balik untuk peserta...">{{ old('catatan_mentor') }}</textarea>
        </label>
        <div class="aksi">
            <a href="{{ route('mentor.logbook.index') }}">Batal</a>
            <button class="btn">Simpan Pemeriksaan</button>
        </div>
    </form>
</div>
@else
<a class="btn btn-garis" href="{{ route('mentor.logbook.index') }}">Kembali ke Daftar</a>
@endif
@endsection
