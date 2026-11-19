@extends('layouts.app')
@section('judul', 'Periksa Logbook')
@section('konten')
@php
    $statusKelas = ['Dikirim' => 'status-peringatan', 'Disetujui' => 'status-sukses', 'Perlu Perbaikan' => 'status-gagal'];
@endphp
<div class="judul-aksi">
    <h1>Periksa Logbook</h1>
</div>

<div class="kartu">
    <h2>Menunggu pemeriksaan</h2>
    <table>
        <thead><tr><th>Peserta</th><th>Tanggal Kegiatan</th><th>Dikirim</th><th></th></tr></thead>
        <tbody>
        @forelse ($antrean as $l)
            <tr>
                <td>{{ $l->pesertaMagang->pengguna->nama_lengkap }}</td>
                <td>{{ $l->tanggal_kegiatan->translatedFormat('d M Y') }}</td>
                <td class="redup">{{ $l->waktu_kirim?->translatedFormat('d M Y, H:i') }}</td>
                <td><a href="{{ route('mentor.logbook.show', $l) }}"><b>Tinjau</b></a></td>
            </tr>
        @empty
            <tr><td colspan="4" class="redup">Tidak ada logbook yang menunggu pemeriksaan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="kartu">
    <h2>Sudah diperiksa</h2>
    <table>
        <thead><tr><th>Peserta</th><th>Tanggal Kegiatan</th><th>Status</th><th>Diperiksa</th><th></th></tr></thead>
        <tbody>
        @forelse ($terdahulu as $l)
            <tr>
                <td>{{ $l->pesertaMagang->pengguna->nama_lengkap }}</td>
                <td>{{ $l->tanggal_kegiatan->translatedFormat('d M Y') }}</td>
                <td><span class="status {{ $statusKelas[$l->status] ?? 'status-netral' }}">{{ $l->status }}</span></td>
                <td class="redup">{{ $l->waktu_periksa?->translatedFormat('d M Y, H:i') ?? '—' }}</td>
                <td><a href="{{ route('mentor.logbook.show', $l) }}">Detail</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="redup">Belum ada logbook yang diperiksa.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
