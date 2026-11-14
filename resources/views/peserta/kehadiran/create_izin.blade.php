@extends('layouts.app')
@section('judul', 'Ajukan Izin')
@section('konten')
<h1>Ajukan Izin Ketidakhadiran</h1>

<div class="kartu">
    <p class="redup">Izin diajukan untuk tanggal dalam periode magang Anda
        ({{ $peserta->tanggal_mulai->translatedFormat('d M Y') }} – {{ $peserta->tanggal_selesai->translatedFormat('d M Y') }})
        yang belum memiliki catatan kehadiran. Izin yang disetujui mentor tidak dihitung sebagai hari hadir.</p>

    @if ($peserta->status_magang !== 'Aktif')
        <div class="alert alert-info">Status magang Anda bukan Aktif, izin tidak dapat diajukan.</div>
    @else
        <form class="form" method="POST" action="{{ route('peserta.kehadiran.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="jenis" value="IZIN">
            <div class="dua-kolom">
                <label>Tanggal izin
                    <input type="date" name="tanggal_kegiatan" value="{{ old('tanggal_kegiatan', today()->toDateString()) }}"
                           min="{{ $peserta->tanggal_mulai->toDateString() }}" max="{{ $peserta->tanggal_selesai->toDateString() }}">
                </label>
                <label>Jenis izin
                    <select name="jenis_izin">
                        <option value="Penuh" @selected(old('jenis_izin') === 'Penuh')>Penuh (tidak hadir seharian)</option>
                        <option value="Sebagian" @selected(old('jenis_izin') === 'Sebagian')>Sebagian (datang terlambat / pulang lebih awal)</option>
                    </select>
                </label>
            </div>
            <label>Alasan <input name="alasan" maxlength="150" value="{{ old('alasan') }}" placeholder="Contoh: Sakit demam"></label>
            <label>Keterangan (opsional) <textarea name="keterangan" rows="3">{{ old('keterangan') }}</textarea></label>
            <label>Bukti pendukung (opsional, PDF/JPG/PNG maks. 2 MB) <input type="file" name="lampiran" accept=".pdf,.jpg,.jpeg,.png"></label>
            <div class="aksi">
                <a href="{{ route('peserta.kehadiran.index') }}">Batal</a>
                <button class="btn">Ajukan Izin</button>
            </div>
        </form>
    @endif
</div>
@endsection
