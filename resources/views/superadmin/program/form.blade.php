@extends('layouts.app')
@section('judul', $program->exists ? 'Ubah Program' : 'Tambah Program')
@section('konten')
<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('superadmin.program-magang.index') }}"><x-ikon nama="kembali" /> Kembali ke daftar program</a>
        <h1>{{ $program->exists ? 'Ubah Program Magang' : 'Tambah Program Magang' }}</h1>
        <p class="sub-judul">Lengkapi informasi program. Program hanya tampil di halaman publik bila berstatus <b>Dipublikasikan</b> dan berada dalam periode pendaftaran.</p>
    </div>
</div>
<form class="kartu form form-kartu" method="POST"
      action="{{ $program->exists ? route('superadmin.program-magang.update', $program) : route('superadmin.program-magang.store') }}">
    @csrf
    @if ($program->exists) @method('PUT') @endif

    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat"><x-ikon nama="koper" /></span> Informasi program</h2>
            <p>Nama dan periode pendaftaran yang akan dilihat calon peserta.</p>
        </div>
        <div class="form-bagian-isi">
            <label>Nama program <input name="nama_program" value="{{ old('nama_program', $program->nama_program) }}" maxlength="150" placeholder="mis. Magang Reguler Semester Genap"></label>
            <div class="dua-kolom">
                <label>Tanggal buka <input type="date" name="tanggal_buka" value="{{ old('tanggal_buka', $program->tanggal_buka?->format('Y-m-d')) }}"></label>
                <label>Tanggal tutup <input type="date" name="tanggal_tutup" value="{{ old('tanggal_tutup', $program->tanggal_tutup?->format('Y-m-d')) }}"></label>
            </div>
            <label>Informasi <textarea name="informasi" rows="3" placeholder="Deskripsi singkat program">{{ old('informasi', $program->informasi) }}</textarea></label>
        </div>
    </div>

    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat kuning"><x-ikon nama="berkas" /></span> Persyaratan</h2>
            <p>Ketentuan umum dan berkas yang wajib diunggah calon peserta saat mendaftar.</p>
        </div>
        <div class="form-bagian-isi">
            <label>Persyaratan (teks) <textarea name="persyaratan" rows="3" placeholder="mis. Mahasiswa aktif minimal semester 5">{{ old('persyaratan', $program->persyaratan) }}</textarea></label>
            @if ($program->exists && $program->syaratDokumenProgram->isNotEmpty())
                <div class="info-kotak">
                    <x-ikon nama="info" />
                    <div>
                        <p><b>Syarat dokumen saat ini:</b></p>
                        <div class="chip-grup" style="margin-top:8px">
                            @foreach ($program->syaratDokumenProgram->sortBy('urutan') as $syarat)
                                <span class="chip">{{ $syarat->nama_syarat }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
            <label>Tambah syarat dokumen
                <textarea name="syarat_baru" rows="3" placeholder="Satu per baris, mis.&#10;KTM&#10;CV&#10;Transkrip">{{ old('syarat_baru') }}</textarea>
                <small class="redup">Tulis satu syarat per baris, mis. KTM / CV / Transkrip.</small>
            </label>
        </div>
    </div>

    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat olive"><x-ikon nama="mata" /></span> Publikasi</h2>
            <p>Draft hanya terlihat oleh admin. Ditutup menghentikan pendaftaran baru.</p>
        </div>
        <div class="form-bagian-isi">
            <label>Status publikasi
                <select name="status_publikasi">
                    @foreach (\App\Models\ProgramMagang::STATUS as $s)
                        <option @selected(old('status_publikasi', $program->status_publikasi) === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </div>

    <div class="form-kaki">
        <a class="btn btn-halus" href="{{ route('superadmin.program-magang.index') }}">Batal</a>
        <button class="btn"><x-ikon nama="centang-saja" /> Simpan</button>
    </div>
</form>
@endsection
