@extends('layouts.app')
@section('judul', $program->exists ? 'Ubah Program' : 'Tambah Program')
@section('konten')
<h1>{{ $program->exists ? 'Ubah Program Magang' : 'Tambah Program Magang' }}</h1>
<form class="kartu form" method="POST"
      action="{{ $program->exists ? route('superadmin.program-magang.update', $program) : route('superadmin.program-magang.store') }}">
    @csrf
    @if ($program->exists) @method('PUT') @endif
    <label>Nama program <input name="nama_program" value="{{ old('nama_program', $program->nama_program) }}" maxlength="150"></label>
    <div class="dua-kolom">
        <label>Tanggal buka <input type="date" name="tanggal_buka" value="{{ old('tanggal_buka', $program->tanggal_buka?->format('Y-m-d')) }}"></label>
        <label>Tanggal tutup <input type="date" name="tanggal_tutup" value="{{ old('tanggal_tutup', $program->tanggal_tutup?->format('Y-m-d')) }}"></label>
    </div>
    <label>Informasi <textarea name="informasi" rows="3">{{ old('informasi', $program->informasi) }}</textarea></label>
    <label>Persyaratan (teks) <textarea name="persyaratan" rows="3">{{ old('persyaratan', $program->persyaratan) }}</textarea></label>
    @if ($program->exists && $program->syaratDokumenProgram->isNotEmpty())
        <p><b>Syarat dokumen saat ini:</b> {{ $program->syaratDokumenProgram->sortBy('urutan')->pluck('nama_syarat')->join(', ') }}</p>
    @endif
    <label>Tambah syarat dokumen (satu per baris, mis. KTM / CV / Transkrip)
        <textarea name="syarat_baru" rows="3">{{ old('syarat_baru') }}</textarea></label>
    <label>Status publikasi
        <select name="status_publikasi">
            @foreach (\App\Models\ProgramMagang::STATUS as $s)
                <option @selected(old('status_publikasi', $program->status_publikasi) === $s)>{{ $s }}</option>
            @endforeach
        </select>
    </label>
    <div class="aksi"><a href="{{ route('superadmin.program-magang.index') }}">Batal</a> <button class="btn">Simpan</button></div>
</form>
@endsection
