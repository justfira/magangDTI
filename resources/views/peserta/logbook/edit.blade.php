@extends('layouts.app')
@section('judul', 'Perbaiki Logbook')
@section('konten')
<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('peserta.logbook.show', $logbook) }}"><x-ikon nama="kembali" /> Kembali ke detail logbook</a>
        <h1>Perbaiki Logbook</h1>
        <p class="sub-judul">Perbarui uraian aktivitas sesuai umpan balik mentor lalu kirim ulang untuk diperiksa kembali.</p>
    </div>
</div>

@if ($logbook->catatan_mentor)
<div class="alert alert-peringatan">
    <x-ikon nama="peringatan" />
    <div><b>Catatan mentor:</b> {{ $logbook->catatan_mentor }}</div>
</div>
@endif

<form class="kartu form form-kartu" method="POST" action="{{ route('peserta.logbook.update', $logbook) }}">
    @csrf
    @method('PUT')
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat kuning"><x-ikon nama="ubah" /></span> Revisi uraian aktivitas</h2>
            <p>Tanggal kegiatan bersifat tetap dan logbook akan kembali ke status menunggu pemeriksaan setelah dikirim.</p>
        </div>
        <div class="form-bagian-isi">
            <div class="info-kotak">
                <x-ikon nama="kalender" />
                <p>Tanggal kegiatan <b>{{ $logbook->tanggal_kegiatan->translatedFormat('d M Y') }}</b> tidak dapat diubah — perbarui uraian aktivitas di bawah ini.</p>
            </div>

            <label>Uraian aktivitas
                <textarea name="uraian_aktivitas" rows="8" required>{{ old('uraian_aktivitas', $logbook->uraian_aktivitas) }}</textarea>
            </label>
        </div>
    </div>
    <div class="form-kaki">
        <a class="btn btn-halus" href="{{ route('peserta.logbook.show', $logbook) }}">Batal</a>
        <button class="btn"><x-ikon nama="centang-saja" /> Kirim Ulang Logbook</button>
    </div>
</form>
@endsection
