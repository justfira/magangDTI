@extends('layouts.app')
@section('judul', 'Notifikasi')
@section('konten')
<div class="judul-aksi">
    <h1>Notifikasi</h1>
    <form method="POST" action="{{ route('notifikasi.baca-semua') }}">@csrf<button class="btn btn-garis">Tandai semua dibaca</button></form>
</div>
<div class="kartu">
    @forelse ($notifikasi as $n)
        <div class="notif {{ $n->sudah_dibaca ? '' : 'baru' }}">
            <div><b>{{ $n->pesan }}</b><br><small class="redup">{{ $n->created_at?->diffForHumans() }} · {{ $n->jenis }}</small></div>
            @unless ($n->sudah_dibaca)
                <form method="POST" action="{{ route('notifikasi.baca', $n) }}">@csrf<button class="btn btn-garis">Tandai dibaca</button></form>
            @endunless
        </div>
    @empty
        <p class="redup">Belum ada notifikasi.</p>
    @endforelse
    {{ $notifikasi->links() }}
</div>
@endsection
