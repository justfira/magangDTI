@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')
@section('judul', $judul)
@section('konten')
<div class="kepala-halaman">
    <div>
        <p class="kicker">Dalam pengembangan</p>
        <h1>{{ $judul }}</h1>
        <p class="sub-judul">Halaman ini masih <b>kerangka</b>. Route, hak akses, dan controller sudah siap; tinggal diisi sesuai langkah berikut.</p>
    </div>
</div>
<div class="kartu rancangan">
    <div class="kartu-judul">
        <span class="ikon-bulat kuning"><x-ikon nama="kode" /></span>
        <div>
            <span class="status status-peringatan">{{ $uc }}</span>
            <p class="redup" style="margin:6px 0 0">Aktor: <b>{{ $aktor }}</b></p>
        </div>
    </div>
    <h3><x-ikon nama="tugas" /> Langkah yang perlu dikerjakan</h3>
    <ol>@foreach ($todo as $t)<li>{{ $t }}</li>@endforeach</ol>
    <h3><x-ikon nama="berkas" /> Tabel yang dipakai</h3>
    <p>@foreach ($tabel as $t)<code>{{ $t }}</code>@endforeach</p>
</div>
@endsection
