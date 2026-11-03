@extends('layouts.app')
@section('judul', 'Program Magang')
@section('konten')
<div class="judul-aksi">
    <h1>Program Magang</h1>
    <a class="btn" href="{{ route('superadmin.program-magang.create') }}">+ Tambah Program</a>
</div>
<div class="kartu">
<table>
    <thead><tr><th>Nama Program</th><th>Periode</th><th>Status</th><th>Syarat</th><th>Pengajuan</th><th></th></tr></thead>
    <tbody>
    @forelse ($program as $p)
        <tr>
            <td>{{ $p->nama_program }}</td>
            <td>{{ $p->tanggal_buka->format('d/m/Y') }} – {{ $p->tanggal_tutup->format('d/m/Y') }}</td>
            <td><span class="status">{{ $p->status_publikasi }}</span></td>
            <td>{{ $p->syarat_dokumen_program_count }}</td>
            <td>{{ $p->pengajuan_count }}</td>
            <td><a href="{{ route('superadmin.program-magang.edit', $p) }}">Ubah</a></td>
        </tr>
    @empty
        <tr><td colspan="6" class="redup">Belum ada program.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $program->links() }}
</div>
@endsection
