@use('App\Services\AlurPenerimaanService', 'Alur')
@extends('layouts.app')
@section('judul', 'Verifikasi Pengajuan')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/penerimaan.css') }}">
@endpush
@section('konten')
@php
    $tautan = fn (array $ubah) => route('superadmin.verifikasi.index', array_filter(array_merge(
        ['status' => $status, 'jalur' => $jalur, 'cari' => $cari ?: null], $ubah)));
@endphp

<div class="kepala-halaman">
    <div>
        <p class="kicker">Rekrutmen · Kelengkapan</p>
        <h1>Verifikasi Pengajuan</h1>
        <p class="sub-judul">Periksa kelengkapan pengajuan dari jalur Rekrutmen DTI dan jalur Institusi, lalu tetapkan Lengkap atau Perlu Perbaikan.</p>
    </div>
</div>

<div class="statistik-grid">
    <div class="stat stat--kuning">
        <span class="ikon-bulat"><x-ikon nama="jam" /></span>
        <div class="nilai">{{ (int) ($jumlah['Diajukan'] ?? 0) }}</div>
        <div class="label">Diajukan · perlu diperiksa</div>
    </div>
    <div class="stat stat--peru">
        <span class="ikon-bulat"><x-ikon nama="peringatan" /></span>
        <div class="nilai">{{ (int) ($jumlah['Perlu Perbaikan'] ?? 0) }}</div>
        <div class="label">Perlu perbaikan</div>
    </div>
    <div class="stat">
        <span class="ikon-bulat"><x-ikon nama="papan-centang" /></span>
        <div class="nilai">{{ (int) ($jumlah['Lengkap'] ?? 0) }}</div>
        <div class="label">Lengkap · di Kepala Seksi</div>
    </div>
</div>

<nav class="tab-bar" aria-label="Filter status">
    <a class="tab {{ $status === null ? 'aktif' : '' }}" href="{{ $tautan(['status' => null]) }}">Semua ({{ $total }})</a>
    @foreach ($tab as $s)
        <a class="tab {{ $status === $s ? 'aktif' : '' }}" href="{{ $tautan(['status' => $s]) }}">{{ $s === 'Selesai Diputuskan' ? 'Selesai' : $s }} ({{ (int) ($jumlah[$s] ?? 0) }})</a>
    @endforeach
</nav>

<div class="kartu kartu-tabel">
    <form class="pn-filter" method="GET" action="{{ route('superadmin.verifikasi.index') }}">
        @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        <span class="input-ikon">
            <x-ikon nama="cari" />
            <input name="cari" value="{{ $cari }}" placeholder="Cari nama pendaftar, institusi, atau kode" aria-label="Cari pengajuan">
        </span>
        <select name="jalur" aria-label="Jalur">
            <option value="">Semua jalur</option>
            <option value="DTI" @selected($jalur === 'DTI')>Rekrutmen DTI</option>
            <option value="INSTITUSI" @selected($jalur === 'INSTITUSI')>Institusi</option>
        </select>
        <button class="btn"><x-ikon nama="filter" /> Filter</button>
        @if ($cari !== '' || $jalur)
            <a class="btn btn-halus" href="{{ $tautan(['cari' => null, 'jalur' => null]) }}">Atur ulang</a>
        @endif
    </form>

    <div class="tabel-wadah">
    <table>
        <thead><tr><th>Pendaftar</th><th>Jalur</th><th>Diajukan</th><th>Status</th><th class="aksi-tabel">Aksi</th></tr></thead>
        <tbody>
        @forelse ($pengajuan as $p)
            <tr>
                <td>
                    <div class="sel-utama">
                        <span class="inisial v{{ $loop->index % 5 }}">
                            @if ($p->jalur === 'INSTITUSI')<x-ikon nama="gedung" />@else{{ mb_strtoupper(mb_substr(Alur::namaPengajuan($p), 0, 1)) }}@endif
                        </span>
                        <div>
                            <b>{{ Alur::namaPengajuan($p) }}</b>
                            <small>{{ $p->kode_pengajuan }} · {{ $p->jalur === 'INSTITUSI' ? $p->calon_peserta_count.' peserta' : ($p->program?->nama_program ?? '—') }}</small>
                        </div>
                    </div>
                </td>
                <td><span class="chip {{ $p->jalur === 'INSTITUSI' ? 'chip-hijau' : '' }}">{{ Alur::labelJalur($p->jalur) }}</span></td>
                <td><span class="sel-ikon"><x-ikon nama="kalender" />{{ $p->tanggal_pengajuan?->format('d/m/Y') }}</span></td>
                <td><span class="status {{ Alur::kelasStatus($p->status_pengajuan) }}">{{ $p->status_pengajuan }}</span></td>
                <td class="aksi-tabel">
                    <a class="btn {{ $p->status_pengajuan === 'Diajukan' ? '' : 'btn-garis' }} btn-kecil" href="{{ route('superadmin.verifikasi.show', $p) }}">
                        @if ($p->status_pengajuan === 'Diajukan')<x-ikon nama="papan-centang" /> Periksa @else<x-ikon nama="mata" /> Detail @endif
                    </a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">
                <div class="kosong">
                    <div class="ilustrasi"><x-ikon nama="kotak-masuk" /></div>
                    <b>{{ Alur::PESAN_KOSONG }}</b>
                    @if ($cari !== '' || $jalur || $status) Coba ubah kata kunci atau filter. @else Belum ada pengajuan yang masuk. @endif
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    {{ $pengajuan->links() }}
</div>
@endsection
