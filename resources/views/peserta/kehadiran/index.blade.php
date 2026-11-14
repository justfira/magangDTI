@extends('layouts.app')
@section('judul', 'Izin & Presensi Susulan')
@section('konten')
<div class="judul-aksi">
    <h1>Izin &amp; Presensi Susulan</h1>
    <div class="aksi">
        <a class="btn btn-garis" href="{{ route('peserta.kehadiran.create', ['jenis' => 'SUSULAN']) }}">+ Presensi Susulan</a>
    </div>
</div>

<div class="kartu">
    <table>
        <thead><tr><th>Tanggal</th><th>Jenis</th><th>Alasan</th><th>Status</th><th>Catatan Mentor</th></tr></thead>
        <tbody>
        @forelse ($pengajuan as $p)
            <tr>
                <td>{{ $p->tanggal_kegiatan->translatedFormat('d M Y') }}</td>
                <td>{{ $p->jenis === 'IZIN' ? 'Izin ('.$p->jenis_izin.')' : 'Presensi susulan' }}</td>
                <td>{{ \Illuminate\Support\Str::limit($p->alasan, 80) }}</td>
                <td><span class="status">{{ $p->status }}</span></td>
                <td>{{ $p->catatan_keputusan ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="redup">Belum ada pengajuan.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $pengajuan->links() }}
</div>
@endsection
