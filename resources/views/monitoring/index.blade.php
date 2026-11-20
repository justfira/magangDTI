@extends('layouts.app')
@section('judul', 'Monitoring Program Magang')
@section('konten')
<div class="kepala-halaman">
    <h1>Monitoring Program Magang</h1>
    <p class="sub-judul">
        {{ now()->translatedFormat('l, d F Y') }} ·
        {{ $cakupanKasi ? 'Seksi '.($daftarSeksi->first()?->nama_seksi ?? '-') : 'Semua seksi' }} · hanya-baca
    </p>
</div>

<div class="grid-kartu">
    @foreach ($ringkasan as $label => $nilai)
        <div class="kartu angka">
            <div class="nilai">{{ $nilai }}</div>
            <div class="label">{{ $label }}</div>
        </div>
    @endforeach
</div>

<div class="kartu">
    <div class="kartu-kepala"><h2>Peserta per Seksi</h2></div>
    @if (empty($perSeksi))
        <p class="redup">Belum ada data seksi.</p>
    @else
        <table>
            <thead>
                <tr><th>Seksi</th>@foreach ($daftarStatus as $s)<th>{{ $s }}</th>@endforeach<th>Total</th></tr>
            </thead>
            <tbody>
            @foreach ($perSeksi as $baris)
                <tr>
                    <td>{{ $baris['nama'] }}</td>
                    @foreach ($daftarStatus as $s)<td>{{ $baris['jumlah'][$s] }}</td>@endforeach
                    <td><b>{{ array_sum($baris['jumlah']) }}</b></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="kartu">
    <div class="kartu-kepala"><h2>Daftar Peserta</h2></div>

    <div class="tab-bar">
        <a class="tab {{ $filterStatus === null ? 'aktif' : '' }}" href="{{ route('monitoring', array_filter(['seksi' => $filterSeksi])) }}">Semua status</a>
        @foreach ($daftarStatus as $s)
            <a class="tab {{ $filterStatus === $s ? 'aktif' : '' }}" href="{{ route('monitoring', array_filter(['seksi' => $filterSeksi, 'status' => $s])) }}">{{ $s }}</a>
        @endforeach
    </div>

    @unless ($cakupanKasi)
        <form class="form" method="GET" action="{{ route('monitoring') }}">
            @if ($filterStatus)<input type="hidden" name="status" value="{{ $filterStatus }}">@endif
            <label>Seksi
                <select name="seksi" onchange="this.form.submit()">
                    <option value="">Semua seksi</option>
                    @foreach ($daftarSeksi as $s)
                        <option value="{{ $s->id_seksi }}" @selected($filterSeksi === $s->id_seksi)>{{ $s->nama_seksi }}</option>
                    @endforeach
                </select>
            </label>
            <noscript><div class="aksi"><button class="btn btn-garis btn-kecil">Terapkan</button></div></noscript>
        </form>
    @endunless

    @if ($peserta->isEmpty())
        <div class="kosong">
            <b>Belum ada data peserta</b>
            Tidak ada peserta yang sesuai dengan filter ini.
        </div>
    @else
        <table>
            <thead>
                <tr><th>Peserta</th><th>Seksi</th><th>Mentor</th><th>Periode</th><th>Status</th><th>Hari hadir</th><th>Presensi hari ini</th><th>Penyelesaian</th></tr>
            </thead>
            <tbody>
            @foreach ($peserta as $p)
                <tr>
                    <td><b>{{ $p->pengguna?->nama_lengkap ?? '-' }}</b></td>
                    <td>{{ $p->seksi?->nama_seksi ?? 'Belum ditetapkan' }}</td>
                    <td>{{ $p->mentor?->pengguna?->nama_lengkap ?? 'Belum ditetapkan' }}</td>
                    <td>{{ $p->tanggal_mulai?->format('d/m/Y') ?? '-' }} – {{ $p->tanggal_selesai?->format('d/m/Y') ?? '-' }}</td>
                    <td>
                        <span class="status {{ match ($p->status_magang) { 'Aktif' => 'status-info', 'Selesai' => 'status-sukses', 'Dihentikan' => 'status-gagal', default => 'status-netral' } }}">{{ $p->status_magang }}</span>
                    </td>
                    <td>{{ $p->hadir_periode_count }} hari</td>
                    <td>
                        @if ($p->status_magang !== 'Aktif')
                            <span class="redup">-</span>
                        @elseif ($p->hadir_hari_ini)
                            <span class="status status-sukses">Sudah</span>
                        @else
                            <span class="status status-peringatan">Belum</span>
                        @endif
                    </td>
                    <td>@include('kepala-seksi.penyelesaian._status', ['penyelesaian' => $p->penyelesaianMagang->first()])</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $peserta->links() }}
    @endif
</div>
<p class="petunjuk">Hari hadir = jumlah hari presensi tercatat dalam periode magang. Presensi hari ini hanya ditampilkan untuk peserta berstatus Aktif.</p>
@endsection
