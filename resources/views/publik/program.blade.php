@extends('layouts.guest')
@section('judul', 'Informasi Program Magang')
@section('konten')
<h1>Program Magang DTI yang Sedang Dibuka</h1>
@forelse ($program as $p)
    <div class="kartu">
        <h2>{{ $p->nama_program }}</h2>
        <p class="redup">Periode pendaftaran: {{ $p->tanggal_buka->translatedFormat('d M Y') }} – {{ $p->tanggal_tutup->translatedFormat('d M Y') }}</p>
        @if ($p->informasi)<p>{{ $p->informasi }}</p>@endif
        @if ($p->syaratDokumenProgram->isNotEmpty())
            <p><b>Berkas persyaratan:</b> {{ $p->syaratDokumenProgram->sortBy('urutan')->pluck('nama_syarat')->join(', ') }}</p>
        @endif
        <a class="btn" href="{{ route('pendaftaran.create', $p) }}">Daftar Sekarang</a>
    </div>
@empty
    <div class="kartu"><p>Belum ada program rekrutmen yang dibuka.</p></div>
@endforelse
@endsection
