@extends('layouts.app')
@section('judul', 'Verifikasi Izin & Presensi Susulan')
@section('konten')
<h1>Verifikasi Izin &amp; Presensi Susulan</h1>

<div class="kartu">
    <form method="GET" class="dua-kolom">
        <label>Status
            <select name="status" onchange="this.form.submit()">
                <option value="">Semua</option>
                @foreach (\App\Models\PengajuanKehadiran::STATUS as $s)
                    <option value="{{ $s }}" @selected($status === $s)>{{ $s }} ({{ $jumlah[$s] ?? 0 }})</option>
                @endforeach
            </select>
        </label>
        <label>Jenis
            <select name="jenis" onchange="this.form.submit()">
                <option value="">Semua</option>
                <option value="SUSULAN" @selected($jenis === 'SUSULAN')>Presensi susulan</option>
                <option value="IZIN" @selected($jenis === 'IZIN')>Izin</option>
            </select>
        </label>
    </form>
</div>

@forelse ($pengajuan as $p)
    <div class="kartu">
        <div class="judul-aksi">
            <h2>{{ $p->pesertaMagang->pengguna->nama_lengkap }}</h2>
            <span class="status">{{ $p->status }}</span>
        </div>
        <table>
            <tr><th>Jenis</th><td>{{ $p->jenis === 'IZIN' ? 'Izin ('.$p->jenis_izin.')' : 'Presensi susulan' }}</td></tr>
            <tr><th>Tanggal kegiatan</th><td>{{ $p->tanggal_kegiatan->translatedFormat('l, d F Y') }}</td></tr>
            <tr><th>Diajukan</th><td>{{ $p->waktu_pengajuan->format('d/m/Y H:i') }}</td></tr>
            <tr><th>Alasan</th><td>{{ $p->alasan }}</td></tr>
            @if ($p->keterangan)<tr><th>Keterangan</th><td>{{ $p->keterangan }}</td></tr>@endif
            @if ($p->jenis === 'SUSULAN')
                <tr><th>Lokasi pengajuan</th><td>{{ $p->latitude }}, {{ $p->longitude }} (akurasi ± {{ round($p->akurasi_meter) }} m)</td></tr>
            @endif
            @if ($p->catatan_keputusan)<tr><th>Catatan keputusan</th><td>{{ $p->catatan_keputusan }}</td></tr>@endif
        </table>

        @if ($p->status === 'Menunggu Verifikasi')
            <div class="dua-kolom">
                <form class="form" method="POST" action="{{ route('mentor.kehadiran.setujui', $p) }}">@csrf
                    <label>Catatan (opsional) <textarea name="catatan_keputusan" rows="2"></textarea></label>
                    <button class="btn">Setujui</button>
                </form>
                <form class="form" method="POST" action="{{ route('mentor.kehadiran.tolak', $p) }}">@csrf
                    <label>Alasan penolakan (wajib) <textarea name="catatan_keputusan" rows="2"></textarea></label>
                    <button class="btn btn-garis">Tolak</button>
                </form>
            </div>
        @endif
    </div>
@empty
    <div class="kartu"><p class="redup">Tidak ada pengajuan.</p></div>
@endforelse
{{ $pengajuan->links() }}
@endsection
