@extends('layouts.app')
@section('judul', 'Kelola Presensi')
@section('konten')
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
    <table>
        <thead><tr><th>Peserta</th><th>Sumber</th><th>Check-in</th><th>Check-out</th><th>Keterangan</th><th></th></tr></thead>
        <tbody>
        @forelse ($presensi as $p)
            <tr>
                <td>{{ $p->pesertaMagang->pengguna->nama_lengkap }}</td>
                <td>{{ $p->sumber }}</td>
                <td>{{ $p->waktu_checkin?->format('H:i') ?? '—' }}</td>
                <td>{{ $p->waktu_checkout?->format('H:i') ?? '—' }}</td>
                <td>
                    @if ($p->sumber === 'REGULER' && ! $p->waktu_checkout)
                        <span class="status">Belum check-out</span>
                    @endif
                    @if ($p->koreksi_presensi_count)
                        <span class="redup">Dikoreksi {{ $p->koreksi_presensi_count }}×</span>
                    @endif
                </td>
                <td><a href="{{ route('superadmin.presensi.koreksi', $p) }}">Koreksi</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="redup">Tidak ada data presensi pada tanggal ini.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $presensi->links() }}
</div>
@endsection
