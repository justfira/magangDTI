@extends('layouts.app')
@section('judul', 'Detail Logbook')
@section('konten')
@php
    $statusKelas = ['Dikirim' => 'status-peringatan', 'Disetujui' => 'status-sukses', 'Perlu Perbaikan' => 'status-gagal'];
@endphp

<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('peserta.logbook.index') }}"><x-ikon nama="kembali" /> Kembali ke daftar logbook</a>
        <h1>Detail Logbook</h1>
        <p class="sub-judul">Ringkasan catatan aktivitas harian beserta status pemeriksaan mentor pembimbing.</p>
    </div>
    <div class="kepala-aksi">
        <span class="status {{ $statusKelas[$logbook->status] ?? 'status-netral' }}">{{ $logbook->status }}</span>
        @if ($logbook->status === 'Perlu Perbaikan')
            <a class="btn" href="{{ route('peserta.logbook.edit', $logbook) }}"><x-ikon nama="ubah" /> Perbaiki Logbook</a>
        @endif
    </div>
</div>

@if ($logbook->status === 'Perlu Perbaikan' && $logbook->catatan_mentor)
<div class="alert alert-peringatan">
    <x-ikon nama="peringatan" />
    <div><b>Catatan mentor:</b> {{ $logbook->catatan_mentor }}</div>
</div>
@endif

<div class="kartu">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat"><x-ikon nama="papan-centang" /></span>
            <div>
                <h2>Informasi pemeriksaan</h2>
                <p>Status pengiriman dan catatan pemeriksaan terakhir dari mentor.</p>
            </div>
        </div>
    </div>
    <table>
        <tr>
            <th style="width:200px">Tanggal kegiatan</th>
            <td><span class="sel-ikon"><x-ikon nama="kalender" /> {{ $logbook->tanggal_kegiatan->translatedFormat('l, d M Y') }}</span></td>
        </tr>
        <tr>
            <th>Waktu kirim</th>
            <td>
                @if ($logbook->waktu_kirim)
                    <span class="sel-ikon"><x-ikon nama="jam" /> {{ $logbook->waktu_kirim->translatedFormat('d M Y, H:i') }}</span>
                @else
                    <span class="redup">—</span>
                @endif
            </td>
        </tr>
        <tr><th>Status pemeriksaan</th><td><span class="status {{ $statusKelas[$logbook->status] ?? 'status-netral' }}">{{ $logbook->status }}</span></td></tr>
        <tr>
            <th>Waktu diperiksa</th>
            <td>
                @if ($logbook->waktu_periksa)
                    <span class="sel-ikon"><x-ikon nama="jam" /> {{ $logbook->waktu_periksa->translatedFormat('d M Y, H:i') }}</span>
                @else
                    <span class="redup">Belum diperiksa</span>
                @endif
            </td>
        </tr>
        <tr><th>Catatan mentor</th><td>{{ $logbook->catatan_mentor ?? '—' }}</td></tr>
    </table>
</div>

<div class="kartu">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat olive"><x-ikon nama="buku" /></span>
            <div>
                <h2>Uraian aktivitas</h2>
                <p>Isi kegiatan harian yang Anda laporkan.</p>
            </div>
        </div>
    </div>
    <p style="white-space:pre-line;margin:0">{{ $logbook->uraian_aktivitas }}</p>
</div>
@endsection
