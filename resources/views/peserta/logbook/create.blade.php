@extends('layouts.app')
@section('judul', 'Isi Logbook')
@section('konten')
<div class="kartu form">
    <h1>Isi Logbook</h1>
    <p class="redup">Periode magang Anda: {{ $peserta->tanggal_mulai->translatedFormat('d M Y') }} – {{ $peserta->tanggal_selesai->translatedFormat('d M Y') }}. Satu logbook untuk setiap tanggal kegiatan.</p>
    <form method="POST" action="{{ route('peserta.logbook.store') }}">
        @csrf
        <label>Tanggal kegiatan
            <input type="date" name="tanggal_kegiatan" value="{{ old('tanggal_kegiatan') }}"
                   min="{{ $peserta->tanggal_mulai->toDateString() }}"
                   max="{{ $peserta->tanggal_selesai->toDateString() }}" required>
        </label>
        <label>Uraian aktivitas
            <textarea name="uraian_aktivitas" rows="6" required
                      placeholder="Jelaskan aktivitas yang Anda kerjakan pada tanggal ini...">{{ old('uraian_aktivitas') }}</textarea>
        </label>
        <div class="aksi">
            <a href="{{ route('peserta.logbook.index') }}">Batal</a>
            <button class="btn">Kirim Logbook</button>
        </div>
    </form>
</div>
@endsection
