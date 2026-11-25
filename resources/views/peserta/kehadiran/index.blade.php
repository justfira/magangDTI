@extends('layouts.app')
@section('judul', 'Izin & Presensi Susulan')
@section('konten')
<link rel="stylesheet" href="{{ asset('css/presensi.css') }}">

<div class="judul-aksi">
    <h1>Izin &amp; Presensi Susulan</h1>
    <div class="aksi">
        <a class="btn btn-garis" href="{{ route('peserta.kehadiran.create', ['jenis' => 'SUSULAN']) }}">+ Presensi Susulan</a>
        <a class="btn" href="{{ route('peserta.kehadiran.create', ['jenis' => 'IZIN']) }}">+ Ajukan Izin</a>
    </div>
</div>

<div class="kartu">
    <div class="pr-tabel">
        <table>
            <thead><tr><th>Tanggal</th><th>Jenis</th><th>Alasan</th><th>Status</th><th>Catatan Mentor</th></tr></thead>
            <tbody>
            @forelse ($pengajuan as $p)
                <tr>
                    <td>{{ $p->tanggal_kegiatan->translatedFormat('D, d M Y') }}</td>
                    <td>@include('peserta.presensi._badge', ['status' => $p->jenis === 'IZIN' ? 'Izin' : 'SUSULAN',
                        'label' => $p->jenis === 'IZIN' ? 'Izin '.strtolower($p->jenis_izin) : 'Presensi susulan'])</td>
                    <td>{{ \Illuminate\Support\Str::limit($p->alasan, 80) }}</td>
                    <td>@include('peserta.presensi._badge', ['status' => $p->status])</td>
                    <td>{{ $p->catatan_keputusan ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="redup">Belum ada pengajuan. Ajukan izin jika tidak dapat hadir, atau presensi susulan
                    jika hari ini Anda bertugas di luar kantor dan belum sempat check-in.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $pengajuan->links() }}
</div>
@endsection
