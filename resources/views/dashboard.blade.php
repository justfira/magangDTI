@extends('layouts.app')
@section('judul', $judul)
@section('konten')
<h1>{{ $judul }}</h1>
<div class="grid-kartu">
    @foreach ($kartu as $label => $nilai)
        <div class="kartu angka"><div class="nilai">{{ $nilai }}</div><div class="label">{{ $label }}</div></div>
    @endforeach
</div>
@isset($pengajuan)
    <div class="kartu">
        <h2>Pengajuan Saya</h2>
        @forelse ($pengajuan as $p)
            <p><b>{{ $p->kode_pengajuan }}</b> — {{ $p->program?->nama_program ?? 'Jalur Institusi' }} :
               <span class="status">{{ $p->status_pengajuan }}</span></p>
        @empty
            <p class="redup">Belum ada pengajuan. <a href="{{ route('informasi-program') }}">Lihat program magang yang dibuka</a>.</p>
        @endforelse
    </div>
@endisset
@endsection
