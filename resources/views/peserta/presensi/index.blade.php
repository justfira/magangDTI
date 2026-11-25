@extends('layouts.app')
@section('judul', 'Presensi Harian')
@section('konten')
<link rel="stylesheet" href="{{ asset('css/presensi.css') }}">

<div class="judul-aksi">
    <h1>Presensi Harian</h1>
    <div class="aksi">
        <a class="btn btn-garis" href="{{ route('peserta.kehadiran.index') }}">Izin &amp; Presensi Susulan</a>
    </div>
</div>

@php
    $status = match (true) {
        ! $hariIni => 'Belum check-in',
        $hariIni->sumber === 'REGULER' && ! $hariIni->waktu_checkout => 'Sudah check-in',
        default => 'Hadir',
    };
@endphp

<div class="kartu">
    <div class="pr-hari-ini">
        <div>
            <p class="pr-tanggal">{{ today()->translatedFormat('l, d F Y') }}</p>
            <p class="pr-jam" id="pr-jam" data-server="{{ now()->timestamp * 1000 }}">{{ now()->format('H:i:s') }}</p>
            <div class="pr-ringkas">
                <div><span>Status</span>@include('peserta.presensi._badge', ['status' => $status])</div>
                <div><span>Check-in</span><b>{{ $hariIni?->waktu_checkin?->format('H:i') ?? '--:--' }}</b></div>
                <div><span>Check-out</span><b>{{ $hariIni?->waktu_checkout?->format('H:i') ?? '--:--' }}</b></div>
                @if ($hariIni && $hariIni->sumber !== 'REGULER')
                    <div><span>Sumber</span>@include('peserta.presensi._badge', ['status' => $hariIni->sumber])</div>
                @endif
            </div>
        </div>

        @unless ($alasanTidakBoleh)
            <div>
                @if (! $hariIni)
                    <form method="POST" action="{{ route('peserta.presensi.checkin') }}" data-butuh-lokasi>@csrf
                        <button class="pr-tombol-besar pr-tombol-masuk">Check-in<small>Masuk</small></button>
                    </form>
                @elseif ($hariIni->sumber === 'REGULER' && ! $hariIni->waktu_checkout)
                    <form method="POST" action="{{ route('peserta.presensi.checkout') }}" data-butuh-lokasi>@csrf
                        <button class="pr-tombol-besar pr-tombol-keluar">Check-out<small>Pulang</small></button>
                    </form>
                @endif
            </div>
        @endunless
    </div>

    @if ($alasanTidakBoleh)
        <div class="alert alert-info" style="margin-top:16px">{{ $alasanTidakBoleh }}</div>
    @else
        @include('peserta.presensi._lokasi')
    @endif
    <div class="pr-catatan">📍 Presensi hanya dapat dilakukan dalam radius {{ config('presensi.kantor.radius_meter') }} m dari
        {{ config('presensi.kantor.nama') }} dengan akurasi lokasi maksimal {{ config('presensi.kantor.batas_akurasi_meter') }} m.
        Waktu yang dicatat adalah waktu server.</div>
</div>

<div class="kartu">
    <h2>Riwayat {{ today()->translatedFormat('F Y') }}</h2>
    <div class="pr-tabel">
        <table>
            <thead><tr><th>Tanggal</th><th>Check-in</th><th>Check-out</th><th>Sumber</th><th>Keterangan</th></tr></thead>
            <tbody>
            @forelse ($riwayat as $p)
                <tr>
                    <td>{{ $p->tanggal->translatedFormat('D, d M') }}</td>
                    <td>{{ $p->waktu_checkin?->format('H:i') ?? '—' }}</td>
                    <td>{{ $p->waktu_checkout?->format('H:i') ?? '—' }}</td>
                    <td>@include('peserta.presensi._badge', ['status' => $p->sumber])</td>
                    <td>@include('peserta.presensi._badge', ['status' => ($p->sumber === 'REGULER' && ! $p->waktu_checkout) ? 'Belum check-out' : 'Hadir'])</td>
                </tr>
            @empty
                <tr><td colspan="5" class="redup">Belum ada presensi bulan ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
// Jam berjalan mengikuti waktu server (bukan jam perangkat)
(function () {
    const el = document.getElementById('pr-jam');
    const selisih = Number(el.dataset.server) - Date.now();
    const dua = (n) => String(n).padStart(2, '0');
    setInterval(() => {
        const t = new Date(Date.now() + selisih);
        el.textContent = `${dua(t.getHours())}:${dua(t.getMinutes())}:${dua(t.getSeconds())}`;
    }, 1000);
})();
</script>
@endsection
