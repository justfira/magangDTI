@extends('layouts.app')
@section('judul', 'Notifikasi')
@section('konten')
<div class="kepala-halaman">
    <div>
        <p class="kicker">Kotak masuk</p>
        <h1>Notifikasi</h1>
        <p class="sub-judul">Kabar terbaru tentang pengajuan, persetujuan, dan aktivitas magang Anda.</p>
    </div>
    <div class="kepala-aksi">
        <form method="POST" action="{{ route('notifikasi.baca-semua') }}">@csrf<button class="btn btn-garis"><x-ikon nama="centang-saja" /> Tandai semua dibaca</button></form>
    </div>
</div>
<div class="kartu">
    @forelse ($notifikasi as $n)
        <div class="notif {{ $n->sudah_dibaca ? '' : 'baru' }}">
            <div class="notif-isi">
                <span class="ikon-bulat {{ $n->sudah_dibaca ? 'olive' : '' }}"><x-ikon nama="lonceng" /></span>
                <div>
                    <b>{{ $n->pesan }}</b>
                    <small class="redup">@unless ($n->sudah_dibaca)<span class="titik-baru"></span>@endunless {{ $n->created_at?->diffForHumans() }} · {{ $n->jenis }}</small>
                </div>
            </div>
            @unless ($n->sudah_dibaca)
                <form method="POST" action="{{ route('notifikasi.baca', $n) }}">@csrf<button class="btn btn-garis btn-kecil">Tandai dibaca</button></form>
            @endunless
        </div>
    @empty
        <div class="kosong">
            <div class="ilustrasi"><x-ikon nama="lonceng" /></div>
            <b>Belum ada notifikasi</b>
            Semua kabar terbaru akan muncul di sini.
        </div>
    @endforelse
    {{ $notifikasi->links() }}
</div>
@endsection
