@extends('layouts.app')
@section('judul', 'Ajukan Presensi Susulan')
@section('konten')
<h1>Ajukan Presensi Susulan</h1>

<div class="kartu">
    <p class="redup">Presensi susulan diajukan <b>pada hari yang sama</b> setelah Anda kembali ke kantor DTI dari kegiatan magang
        di luar kantor. Lokasi Anda akan diperiksa, lalu pengajuan diverifikasi oleh mentor.</p>

    @if ($alasanTidakBoleh)
        <div class="alert alert-info">{{ $alasanTidakBoleh }}</div>
    @elseif ($sudahHadir)
        <div class="alert alert-info">Kehadiran Anda hari ini sudah tercatat, presensi susulan tidak diperlukan.</div>
    @else
        <form class="form" method="POST" action="{{ route('peserta.kehadiran.store') }}" data-butuh-lokasi>
            @csrf
            <input type="hidden" name="jenis" value="SUSULAN">
            <label>Tanggal kegiatan
                <input type="date" name="tanggal_kegiatan" value="{{ today()->toDateString() }}" readonly>
            </label>
            <label>Alasan tidak melakukan presensi reguler
                <textarea name="alasan" rows="4" placeholder="Contoh: mendampingi tim DTI melakukan instalasi jaringan di Fakultas Teknik sejak pagi.">{{ old('alasan') }}</textarea>
            </label>
            <div class="aksi">
                <a href="{{ route('peserta.kehadiran.index') }}">Batal</a>
                <button class="btn">Ajukan</button>
            </div>
        </form>
        @include('peserta.presensi._lokasi')
    @endif
</div>
@endsection
