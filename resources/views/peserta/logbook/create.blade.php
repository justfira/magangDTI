@extends('layouts.app')
@section('judul', 'Isi Logbook')
@section('konten')
<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('peserta.logbook.index') }}"><x-ikon nama="kembali" /> Kembali ke daftar logbook</a>
        <h1>Isi Logbook</h1>
        <p class="sub-judul">Catat kegiatan harian Anda selama menjalani magang di Direktorat Teknologi Informasi.</p>
    </div>
</div>

<form class="kartu form form-kartu" method="POST" action="{{ route('peserta.logbook.store') }}">
    @csrf
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat"><x-ikon nama="buku" /></span> Catatan kegiatan</h2>
            <p>Setiap tanggal hanya dapat diisi satu logbook dan akan langsung dikirim ke mentor Anda.</p>
        </div>
        <div class="form-bagian-isi">
            <div class="info-kotak">
                <x-ikon nama="kalender" />
                <p>Periode magang Anda: <b>{{ $peserta->tanggal_mulai->translatedFormat('d M Y') }} – {{ $peserta->tanggal_selesai->translatedFormat('d M Y') }}</b>.</p>
            </div>

            <label>Tanggal kegiatan
                <input type="date" name="tanggal_kegiatan" value="{{ old('tanggal_kegiatan') }}"
                       min="{{ $peserta->tanggal_mulai->toDateString() }}"
                       max="{{ $peserta->tanggal_selesai->toDateString() }}" required>
            </label>
            <label>Uraian aktivitas
                <textarea name="uraian_aktivitas" rows="6" required
                          placeholder="Jelaskan aktivitas yang Anda kerjakan pada tanggal ini...">{{ old('uraian_aktivitas') }}</textarea>
            </label>
        </div>
    </div>
    <div class="form-kaki">
        <a class="btn btn-halus" href="{{ route('peserta.logbook.index') }}">Batal</a>
        <button class="btn"><x-ikon nama="centang-saja" /> Kirim Logbook</button>
    </div>
</form>
@endsection
