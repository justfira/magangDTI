@extends('layouts.app')
@section('judul', 'Program Magang')
@section('konten')
<div class="kepala-halaman">
    <div>
        <p class="kicker">Rekrutmen</p>
        <h1>Program Magang</h1>
        <p class="sub-judul">Kelola periode rekrutmen, syarat dokumen, dan status publikasi program magang.</p>
    </div>
    <div class="kepala-aksi">
        <a class="btn" href="{{ route('superadmin.program-magang.create') }}"><x-ikon nama="plus" /> Tambah Program</a>
    </div>
</div>
<div class="kartu kartu-tabel">
    <div class="tabel-wadah">
    <table>
        <thead><tr><th>Nama Program</th><th>Periode</th><th>Status</th><th>Syarat</th><th>Pengajuan</th><th class="aksi-tabel">Aksi</th></tr></thead>
        <tbody>
        @forelse ($program as $p)
            @php
                $kelasStatus = match ($p->status_publikasi) {
                    'Dipublikasikan' => 'status-sukses',
                    'Ditutup' => 'status-gagal',
                    default => 'status-netral',
                };
            @endphp
            <tr>
                <td>
                    <div class="sel-utama">
                        <span class="inisial v{{ $loop->index % 5 }}"><x-ikon nama="koper" /></span>
                        <b>{{ $p->nama_program }}</b>
                    </div>
                </td>
                <td><span class="sel-ikon"><x-ikon nama="kalender" />{{ $p->tanggal_buka->format('d/m/Y') }} – {{ $p->tanggal_tutup->format('d/m/Y') }}</span></td>
                <td><span class="status {{ $kelasStatus }}">{{ $p->status_publikasi }}</span></td>
                <td><span class="angka-pil">{{ $p->syarat_dokumen_program_count }}</span></td>
                <td><span class="angka-pil">{{ $p->pengajuan_count }}</span></td>
                <td class="aksi-tabel"><a class="btn btn-garis btn-kecil" href="{{ route('superadmin.program-magang.edit', $p) }}"><x-ikon nama="ubah" /> Ubah</a></td>
            </tr>
        @empty
            <tr><td colspan="6">
                <div class="kosong">
                    <div class="ilustrasi"><x-ikon nama="koper" /></div>
                    <b>Belum ada program.</b>
                    Buat program magang pertama untuk mulai membuka rekrutmen.
                    <br><a class="btn" href="{{ route('superadmin.program-magang.create') }}"><x-ikon nama="plus" /> Tambah Program</a>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    {{ $program->links() }}
</div>
@endsection
