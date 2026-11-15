@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')
@section('judul', $judul)
@section('konten')
<h1>{{ $judul }}</h1>
<div class="kartu rancangan">
    <p><span class="status">{{ $uc }}</span> &nbsp; Aktor: <b>{{ $aktor }}</b></p>
    <p class="redup">Halaman ini masih <b>kerangka</b>. Route, hak akses, dan controller sudah siap; tinggal diisi sesuai langkah berikut.</p>
    <h3>Langkah yang perlu dikerjakan</h3>
    <ol>@foreach ($todo as $t)<li>{{ $t }}</li>@endforeach</ol>
    <h3>Tabel yang dipakai</h3>
    <p>@foreach ($tabel as $t)<code>{{ $t }}</code> @endforeach</p>
</div>
@endsection
