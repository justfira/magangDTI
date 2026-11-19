@extends('layouts.app')
@section('judul', 'Manajemen Akun')
@section('konten')
<div class="kepala-halaman">
    <div>
        <p class="kicker">Pengguna</p>
        <h1>Manajemen Akun</h1>
        <p class="sub-judul">Tambah akun pegawai, atur peran, dan aktifkan atau nonaktifkan akses pengguna.</p>
    </div>
    <div class="kepala-aksi">
        <a class="btn btn-garis" href="{{ route('superadmin.hak-akses') }}"><x-ikon nama="perisai" /> Lihat Hak Akses</a>
        <a class="btn" href="{{ route('superadmin.akun.create') }}"><x-ikon nama="tambah-orang" /> Tambah Akun Pegawai</a>
    </div>
</div>

<form class="kartu filter-bar" method="GET" action="{{ route('superadmin.akun.index') }}">
    <label>Cari nama / email
        <span class="input-ikon">
            <x-ikon nama="cari" />
            <input name="cari" value="{{ request('cari') }}" placeholder="Ketik nama atau email">
        </span>
    </label>
    <label>Peran
        <select name="peran">
            <option value="">Semua peran</option>
            @foreach ($daftarPeran as $p)
                <option value="{{ $p->kode_peran }}" @selected(request('peran') === $p->kode_peran)>{{ $p->nama_peran }}</option>
            @endforeach
        </select>
    </label>
    <label>Status
        <select name="status">
            <option value="">Semua status</option>
            @foreach (['Aktif', 'Nonaktif'] as $s)
                <option @selected(request('status') === $s)>{{ $s }}</option>
            @endforeach
        </select>
    </label>
    <div class="aksi">
        <a class="btn btn-halus" href="{{ route('superadmin.akun.index') }}" title="Atur ulang"><x-ikon nama="atur-ulang" /> Atur ulang</a>
        <button class="btn"><x-ikon nama="filter" /> Terapkan</button>
    </div>
</form>

<div class="kartu kartu-tabel">
    <div class="tabel-wadah">
    <table>
        <thead><tr><th>Pengguna</th><th>Peran</th><th>Seksi / NIP</th><th>Status</th><th class="aksi-tabel">Aksi</th></tr></thead>
        <tbody>
        @forelse ($akun as $a)
            @php
                $pegawai = in_array($a->kodePeran(), \App\Support\Peran::PEGAWAI, true);
                $varianInisial = 'v'.(crc32($a->kodePeran() ?? '') % 5);
            @endphp
            <tr>
                <td>
                    <div class="sel-utama">
                        <span class="inisial {{ $varianInisial }}">{{ mb_strtoupper(mb_substr($a->nama_lengkap, 0, 1)) }}</span>
                        <div>
                            <b>{{ $a->nama_lengkap }}@if ($a->is(auth()->user())) <small class="redup" style="display:inline">(Anda)</small>@endif</b>
                            <small>{{ $a->email }}</small>
                        </div>
                    </div>
                </td>
                <td><span class="chip chip-hijau">{{ $a->peran->nama_peran }}</span></td>
                <td>
                    @if ($a->pegawai)
                        {{ $a->pegawai->seksi?->nama_seksi ?? '—' }}<br><small class="redup">{{ $a->pegawai->nip ?? '—' }}</small>
                    @else
                        <span class="redup">—</span>
                    @endif
                </td>
                <td><span class="status {{ $a->isAktif() ? 'status-sukses' : 'status-gagal' }}">{{ $a->status_akun }}</span></td>
                <td class="aksi-tabel">
                    @if ($pegawai)
                        <a class="btn btn-garis btn-kecil" href="{{ route('superadmin.akun.edit', $a) }}"><x-ikon nama="ubah" /> Ubah</a>
                    @endif
                    @unless ($a->is(auth()->user()))
                        @if ($a->isAktif())
                            <form method="POST" action="{{ route('superadmin.akun.nonaktifkan', $a) }}"
                                  onsubmit="return confirm('Nonaktifkan akun {{ e($a->nama_lengkap) }}? Pengguna tidak akan bisa login.')">@csrf
                                <button class="btn btn-kecil btn-garis-bahaya">Nonaktifkan</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('superadmin.akun.aktifkan', $a) }}">@csrf
                                <button class="btn btn-kecil btn-garis">Aktifkan</button>
                            </form>
                        @endif
                    @endunless
                </td>
            </tr>
        @empty
            <tr><td colspan="5">
                <div class="kosong">
                    <div class="ilustrasi"><x-ikon nama="cari" /></div>
                    <b>Tidak ada akun yang cocok.</b>
                    Coba ubah kata kunci atau filter pencarian.
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    {{ $akun->links() }}
</div>
@endsection
