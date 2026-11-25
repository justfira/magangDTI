@extends('layouts.app')
@section('judul', 'Koreksi Presensi')
@section('konten')
<link rel="stylesheet" href="{{ asset('css/presensi.css') }}">
<h1>Koreksi Presensi</h1>

<div class="kartu">
    <h2>{{ $presensi->pesertaMagang->pengguna->nama_lengkap }} — {{ $presensi->tanggal->translatedFormat('l, d F Y') }}</h2>
    <table>
        <tr><th>Sumber saat ini</th><td>{{ $presensi->sumber }}</td></tr>
        <tr><th>Check-in</th><td>{{ $presensi->waktu_checkin?->format('H:i') ?? '—' }}</td></tr>
        <tr><th>Check-out</th><td>{{ $presensi->waktu_checkout?->format('H:i') ?? '—' }}</td></tr>
    </table>
</div>

<form class="kartu form" method="POST" action="{{ route('superadmin.presensi.koreksi.simpan', $presensi) }}" enctype="multipart/form-data">
    @csrf
    <h2>Data koreksi</h2>
    <div class="dua-kolom">
        <label>Jam check-in <input type="time" name="jam_checkin" value="{{ old('jam_checkin', $presensi->waktu_checkin?->format('H:i')) }}"></label>
        <label>Jam check-out (kosongkan jika tidak ada) <input type="time" name="jam_checkout" value="{{ old('jam_checkout', $presensi->waktu_checkout?->format('H:i')) }}"></label>
    </div>
    <label>Alasan koreksi (wajib) <textarea name="alasan" rows="3" placeholder="Contoh: Peserta lupa check-out karena gangguan jaringan; dikonfirmasi oleh mentor.">{{ old('alasan') }}</textarea></label>
    <label>Bukti pendukung (wajib, PDF/JPG/PNG maks. 2 MB) <input type="file" name="bukti" accept=".pdf,.jpg,.jpeg,.png"></label>
    <p class="redup">Setelah dikoreksi, sumber presensi menjadi <b>KOREKSI</b>. Data sebelum dan sesudah koreksi tersimpan sebagai riwayat.</p>
    <div class="aksi">
        <a href="{{ route('superadmin.presensi.index', ['tanggal' => $presensi->tanggal->toDateString()]) }}">Kembali</a>
        <button class="btn">Simpan Koreksi</button>
    </div>
</form>

<div class="kartu">
    <h2>Riwayat koreksi</h2>
    <table>
        <thead><tr><th>Waktu</th><th>Oleh</th><th>Sebelum</th><th>Sesudah</th><th>Alasan</th><th>Bukti</th></tr></thead>
        <tbody>
        @forelse ($presensi->koreksiPresensi as $k)
            <tr>
                <td>{{ $k->waktu_koreksi->format('d/m/Y H:i') }}</td>
                <td>{{ $k->pengoreksi->pengguna->nama_lengkap }}</td>
                <td>{{ $k->data_sebelum['sumber'] ?? '' }}<br>
                    {{ substr($k->data_sebelum['waktu_checkin'] ?? '', 11, 5) ?: '—' }} – {{ substr($k->data_sebelum['waktu_checkout'] ?? '', 11, 5) ?: '—' }}</td>
                <td>{{ $k->data_sesudah['sumber'] ?? '' }}<br>
                    {{ substr($k->data_sesudah['waktu_checkin'] ?? '', 11, 5) ?: '—' }} – {{ substr($k->data_sesudah['waktu_checkout'] ?? '', 11, 5) ?: '—' }}</td>
                <td>{{ $k->alasan }}</td>
                <td><a href="{{ route('superadmin.presensi.bukti', $k) }}" target="_blank" rel="noopener">Lihat</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="redup">Belum pernah dikoreksi.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
