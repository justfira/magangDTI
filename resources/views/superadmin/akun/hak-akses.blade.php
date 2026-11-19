@extends('layouts.app')
@section('judul', 'Hak Akses')
@section('konten')
<div class="kepala-halaman">
    <div>
        <p class="kicker">Keamanan</p>
        <h1>Hak Akses per Peran</h1>
        <p class="sub-judul">Hak akses ditentukan oleh peran akun. Untuk mengubah hak akses seseorang, ubah perannya melalui <b>Manajemen Akun &rarr; Ubah</b>.</p>
    </div>
    <div class="kepala-aksi">
        <a class="btn" href="{{ route('superadmin.akun.index') }}"><x-ikon nama="pengguna" /> Kelola Akun</a>
    </div>
</div>

<div class="info-kotak">
    <x-ikon nama="perisai" />
    <p>Pembatasan dijalankan di sisi server, sehingga pengguna tidak bisa membuka halaman peran lain meski mengetik alamatnya langsung.</p>
</div>

<div class="peran-grid">
    @foreach ($peran as $p)
        @php $warna = ['', 'kuning', 'peru', 'olive'][$loop->index % 4]; @endphp
        <article class="kartu kartu-peran">
            <div class="kartu-peran-kepala">
                <span class="ikon-bulat {{ $warna }}"><x-ikon nama="perisai" /></span>
                <div>
                    <h2>{{ $p->nama_peran }}</h2>
                    <code>{{ $p->kode_peran }}</code>
                </div>
            </div>
            <div class="chip-grup">
                @forelse ($menuPeran[$p->kode_peran] as $menu)
                    <span class="chip">{{ $menu }}</span>
                @empty
                    <span class="redup">—</span>
                @endforelse
            </div>
            <div class="kartu-peran-angka">
                <div><div class="n">{{ $p->jumlah_aktif }}</div><div class="l">Akun aktif</div></div>
                <div><div class="n">{{ $p->jumlah_nonaktif }}</div><div class="l">Akun nonaktif</div></div>
            </div>
        </article>
    @endforeach
</div>
@endsection
