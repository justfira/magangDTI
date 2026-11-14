@extends('layouts.app')
@section('judul', 'Rekap Kehadiran Bulanan')
@section('konten')
<div class="judul-aksi">
    <h1>Rekap Kehadiran Bulanan</h1>
    <a class="btn btn-garis" href="{{ route('superadmin.presensi.index') }}">Kelola Presensi</a>
</div>

<div class="kartu">
    <form method="GET" class="dua-kolom">
        <label>Periode <input type="month" name="bulan" value="{{ $bulan->format('Y-m') }}" onchange="this.form.submit()"></label>
        <div class="aksi">
            @if ($rekap->isNotEmpty())
                <a class="btn" href="{{ route('superadmin.presensi.csv', ['bulan' => $bulan->format('Y-m')]) }}">Ekspor CSV</a>
            @endif
        </div>
    </form>
</div>

<div class="kartu">
    <h2>{{ $bulan->translatedFormat('F Y') }}</h2>
    @if ($rekap->isEmpty())
        <p class="redup">Data rekap tidak tersedia untuk periode ini.</p>
    @else
        <table>
            <thead><tr><th>Peserta</th><th>Seksi</th><th>Hari Hadir</th><th>Dari Susulan</th><th>Izin Penuh</th><th>Izin Sebagian</th><th>Belum Check-out</th></tr></thead>
            <tbody>
            @foreach ($rekap as $r)
                <tr>
                    <td>{{ $r->pengguna->nama_lengkap }}</td>
                    <td>{{ $r->seksi?->nama_seksi ?? '—' }}</td>
                    <td><b>{{ $r->hadir }}</b></td>
                    <td>{{ $r->hadir_susulan }}</td>
                    <td>{{ $r->izin_penuh }}</td>
                    <td>{{ $r->izin_sebagian }}</td>
                    <td>@if ($r->belum_checkout) <span class="status">{{ $r->belum_checkout }}</span> @else 0 @endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="redup">Hari hadir = presensi reguler dengan check-in &amp; check-out + presensi susulan yang disetujui + presensi hasil koreksi
            (satu tanggal dihitung satu hari). Izin tidak dihitung sebagai hadir. Presensi tanpa check-out tidak dihitung — perbaiki lewat Koreksi Presensi.</p>
    @endif
</div>
@endsection
