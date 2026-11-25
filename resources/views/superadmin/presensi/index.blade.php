@extends('layouts.app')
@section('judul', 'Kelola Presensi')
@section('konten')
<link rel="stylesheet" href="{{ asset('css/presensi.css') }}">

<div class="judul-aksi">
    <h1>Kelola Presensi</h1>
    <a class="btn btn-garis" href="{{ route('superadmin.presensi.rekap') }}">Rekap Bulanan</a>
</div>

<div class="kartu">
    <form method="GET" class="dua-kolom">
        <label>Tanggal <input type="date" name="tanggal" value="{{ $tanggal->toDateString() }}" onchange="this.form.submit()"></label>
        <label>Cari nama peserta <input name="cari" value="{{ $cari }}" placeholder="Ketik lalu tekan Enter"></label>
    </form>
</div>

<div class="kartu">
    <h2>{{ $tanggal->translatedFormat('l, d F Y') }}</h2>
    <div class="pr-tabel">
        <table>
            <thead><tr><th>Peserta</th><th>Check-in</th><th>Check-out</th><th>Sumber</th><th>Keterangan</th><th></th></tr></thead>
            <tbody>
            @forelse ($presensi as $p)
                <tr>
                    <td>{{ $p->pesertaMagang->pengguna->nama_lengkap }}</td>
                    <td>{{ $p->waktu_checkin?->format('H:i') ?? '—' }}</td>
                    <td>{{ $p->waktu_checkout?->format('H:i') ?? '—' }}</td>
                    <td>@include('peserta.presensi._badge', ['status' => $p->sumber])</td>
                    <td>
                        @if ($p->sumber === 'REGULER' && ! $p->waktu_checkout)
                            @include('peserta.presensi._badge', ['status' => 'Belum check-out'])
                        @endif
                        @if ($p->koreksi_presensi_count)
                            <span class="redup">Dikoreksi {{ $p->koreksi_presensi_count }}×</span>
                        @endif
                    </td>
                    <td><a class="btn btn-garis" href="{{ route('superadmin.presensi.koreksi', $p) }}">Koreksi</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="redup">Tidak ada data presensi pada tanggal ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $presensi->links() }}
</div>
@endsection
