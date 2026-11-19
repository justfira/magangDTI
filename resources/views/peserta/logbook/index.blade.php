@extends('layouts.app')
@section('judul', 'Logbook Harian')
@section('konten')
@php
    $statusKelas = ['Dikirim' => 'status-peringatan', 'Disetujui' => 'status-sukses', 'Perlu Perbaikan' => 'status-gagal'];
@endphp
<div class="judul-aksi">
    <h1>Logbook Harian</h1>
    <a class="btn" href="{{ route('peserta.logbook.create') }}">+ Isi Logbook</a>
</div>

@if ($hariKosong->isNotEmpty())
<div class="kartu">
    <h2>Hari hadir tanpa logbook</h2>
    <p class="redup">Hari-hari berikut Anda hadir tetapi belum mengisi logbook. Tanggal dengan izin penuh yang disetujui tidak termasuk kewajiban.</p>
    <div class="chip-grup">
        @foreach ($hariKosong as $presensiHari)
            <span class="chip">{{ $presensiHari->tanggal->translatedFormat('d M Y') }}</span>
        @endforeach
    </div>
    <p class="redup kecil" style="margin:10px 0 0">Gunakan tombol "+ Isi Logbook" untuk mengisi tanggal tersebut.</p>
</div>
@endif

<div class="kartu">
    <table>
        <thead><tr><th>Tanggal</th><th>Uraian Aktivitas</th><th>Status</th><th>Diperiksa</th><th></th></tr></thead>
        <tbody>
        @forelse ($logbook as $l)
            <tr>
                <td>{{ $l->tanggal_kegiatan->translatedFormat('d M Y') }}</td>
                <td>{{ \Illuminate\Support\Str::limit($l->uraian_aktivitas, 80) }}</td>
                <td><span class="status {{ $statusKelas[$l->status] ?? 'status-netral' }}">{{ $l->status }}</span></td>
                <td class="redup">{{ $l->waktu_periksa?->format('d/m H:i') ?? '—' }}</td>
                <td>
                    <a href="{{ route('peserta.logbook.show', $l) }}">Detail</a>
                    @if ($l->status === 'Perlu Perbaikan')
                        &nbsp;<a href="{{ route('peserta.logbook.edit', $l) }}"><b>Perbaiki</b></a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="redup">Belum ada logbook. Klik "+ Isi Logbook" untuk mencatat kegiatan harian Anda.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
