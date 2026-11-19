@extends('layouts.app')
@section('judul', 'Perbaiki Logbook')
@section('konten')
@if ($logbook->catatan_mentor)
<div class="alert alert-info"><b>Catatan mentor:</b> {{ $logbook->catatan_mentor }}</div>
@endif

<div class="kartu form">
    <h1>Perbaiki Logbook</h1>
    <p class="redup">Tanggal kegiatan <b>{{ $logbook->tanggal_kegiatan->translatedFormat('d M Y') }}</b> tidak dapat diubah — perbarui uraian aktivitas, lalu kirim ulang untuk diperiksa mentor.</p>
    <form method="POST" action="{{ route('peserta.logbook.update', $logbook) }}">
        @csrf
        @method('PUT')
        <label>Uraian aktivitas
            <textarea name="uraian_aktivitas" rows="8" required>{{ old('uraian_aktivitas', $logbook->uraian_aktivitas) }}</textarea>
        </label>
        <div class="aksi">
            <a href="{{ route('peserta.logbook.show', $logbook) }}">Batal</a>
            <button class="btn">Kirim Ulang Logbook</button>
        </div>
    </form>
</div>
@endsection
