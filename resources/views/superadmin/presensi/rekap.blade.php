@extends('layouts.app')
@section('judul', 'Rekap Kehadiran Bulanan')
@section('konten')
<link rel="stylesheet" href="{{ asset('css/presensi.css') }}">

<div class="judul-aksi">
    <h1>Rekap Kehadiran Bulanan</h1>
    <a class="btn btn-garis" href="{{ route('superadmin.presensi.index') }}">Kelola Presensi</a>
</div>

<div class="kartu">
    <form method="GET" class="dua-kolom">
        <label>Periode <input type="month" name="bulan" value="{{ $bulan->format('Y-m') }}" onchange="this.form.submit()"></label>
        <div class="aksi">
            @if ($rekap->isNotEmpty())
                <a class="btn" href="{{ route('superadmin.presensi.csv', ['bulan' => $bulan->format('Y-m')]) }}">⬇ Ekspor CSV</a>
            @endif
        </div>
    </form>
</div>

@if ($rekap->isEmpty())
    <div class="kartu"><p class="redup">Data rekap tidak tersedia untuk periode {{ $bulan->translatedFormat('F Y') }}.</p></div>
@else
    <div class="pr-statistik">
        <div><b>{{ $rekap->count() }}</b><span>Peserta pada periode ini</span></div>
        <div><b>{{ $rekap->sum('hadir') }}</b><span>Total hari hadir</span></div>
        <div><b>{{ $rekap->sum('izin_penuh') + $rekap->sum('izin_sebagian') }}</b><span>Izin disetujui</span></div>
        <div><b>{{ $rekap->sum('belum_checkout') }}</b><span>Presensi belum check-out</span></div>
    </div>

    <div class="kartu">
        <h2>{{ $bulan->translatedFormat('F Y') }}</h2>
        <div class="pr-tabel">
            <table>
                <thead><tr><th>Peserta</th><th>Seksi</th><th class="pr-angka">Hari Hadir</th><th class="pr-angka">Dari Susulan</th>
                    <th class="pr-angka">Izin Penuh</th><th class="pr-angka">Izin Sebagian</th><th class="pr-angka">Belum Check-out</th></tr></thead>
                <tbody>
                @foreach ($rekap as $r)
                    <tr>
                        <td>{{ $r->pengguna->nama_lengkap }}</td>
                        <td>{{ $r->seksi?->nama_seksi ?? '—' }}</td>
                        <td class="pr-angka pr-sorot">{{ $r->hadir }}</td>
                        <td class="pr-angka">{{ $r->hadir_susulan }}</td>
                        <td class="pr-angka">{{ $r->izin_penuh }}</td>
                        <td class="pr-angka">{{ $r->izin_sebagian }}</td>
                        <td class="pr-angka">
                            @if ($r->belum_checkout) @include('peserta.presensi._badge', ['status' => 'Belum check-out', 'label' => $r->belum_checkout])
                            @else 0 @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pr-catatan">ℹ️ Hari hadir = presensi reguler dengan check-in &amp; check-out + presensi susulan yang disetujui + presensi
            hasil koreksi (satu tanggal dihitung satu hari). Izin tidak dihitung sebagai hadir. Presensi tanpa check-out tidak dihitung —
            perbaiki lewat Koreksi Presensi.</div>
    </div>
@endif
@endsection
