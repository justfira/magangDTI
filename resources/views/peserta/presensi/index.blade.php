@extends('layouts.app')
@section('judul', 'Presensi Harian')
@section('konten')
<h1>Presensi Harian</h1>

<div class="kartu">
    <h2>{{ today()->translatedFormat('l, d F Y') }}</h2>

    @if ($alasanTidakBoleh)
        <div class="alert alert-info">{{ $alasanTidakBoleh }}</div>
    @endif

    <table>
        <tr><th>Status</th>
            <td>
                @if (! $hariIni) <span class="status">Belum check-in</span>
                @elseif (! $hariIni->waktu_checkout && $hariIni->sumber === 'REGULER') <span class="status">Sudah check-in</span>
                @else <span class="status">Hadir ({{ $hariIni->sumber }})</span>
                @endif
            </td></tr>
        <tr><th>Check-in</th><td>{{ $hariIni?->waktu_checkin?->format('H:i') ?? '—' }}</td></tr>
        <tr><th>Check-out</th><td>{{ $hariIni?->waktu_checkout?->format('H:i') ?? '—' }}</td></tr>
    </table>

    @unless ($alasanTidakBoleh)
        <div class="aksi">
            @if (! $hariIni)
                <form method="POST" action="{{ route('peserta.presensi.checkin') }}" data-butuh-lokasi>@csrf
                    <button class="btn">Check-in</button>
                </form>
            @elseif ($hariIni->sumber === 'REGULER' && ! $hariIni->waktu_checkout)
                <form method="POST" action="{{ route('peserta.presensi.checkout') }}" data-butuh-lokasi>@csrf
                    <button class="btn">Check-out</button>
                </form>
            @endif
        </div>
        @include('peserta.presensi._lokasi')
    @endunless
    <p class="redup">Presensi hanya dapat dilakukan dalam radius {{ config('presensi.kantor.radius_meter') }} m dari
        {{ config('presensi.kantor.nama') }} dengan akurasi lokasi maksimal {{ config('presensi.kantor.batas_akurasi_meter') }} m.</p>
</div>

<div class="kartu">
    <h2>Riwayat Bulan Ini</h2>
    <table>
        <thead><tr><th>Tanggal</th><th>Sumber</th><th>Check-in</th><th>Check-out</th><th>Keterangan</th></tr></thead>
        <tbody>
        @forelse ($riwayat as $p)
            <tr>
                <td>{{ $p->tanggal->translatedFormat('D, d M Y') }}</td>
                <td>{{ $p->sumber }}</td>
                <td>{{ $p->waktu_checkin?->format('H:i') ?? '—' }}</td>
                <td>{{ $p->waktu_checkout?->format('H:i') ?? '—' }}</td>
                <td>
                    @if ($p->sumber === 'REGULER' && ! $p->waktu_checkout)
                        <span class="redup">Belum check-out</span>
                    @else
                        Hadir
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="redup">Belum ada presensi bulan ini.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
