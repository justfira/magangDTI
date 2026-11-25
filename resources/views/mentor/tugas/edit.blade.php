@extends('layouts.app')
@section('judul', 'Ubah Tugas & Tenggat Waktu')
@section('konten')
<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('mentor.tugas.show', $tugas) }}"><x-ikon nama="kembali" /> Kembali ke detail tugas</a>
        <h1>Ubah Tugas &amp; Tenggat Waktu</h1>
        <p class="sub-judul">Perbarui rincian tugas, tenggat waktu penyelesaian, atau instruksi untuk peserta bimbingan.</p>
    </div>
</div>

@php
    $peserta = $tugas->pesertaMagang;
    $namaPeserta = $peserta?->calonPeserta()?->nama_lengkap ?? $peserta?->pengguna?->nama_lengkap ?? 'Peserta';
    $institusi = $peserta?->calonPeserta()?->institusi_asal ?? 'DTI Unand';
@endphp

<form class="kartu form form-kartu" method="POST" action="{{ route('mentor.tugas.update', $tugas) }}">
    @csrf
    @method('PUT')

    {{-- 01. Peserta Bimbingan (Read-only) --}}
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat"><x-ikon nama="pengguna" /></span> Peserta Bimbingan</h2>
            <p>Penerima tugas ini tidak dapat dipindahkan ke peserta lain.</p>
        </div>
        <div class="form-bagian-isi">
            <label>Peserta Magang
                <input type="text" value="{{ $namaPeserta }} ({{ $institusi }})" disabled>
            </label>
        </div>
    </div>

    {{-- 02. Rincian Penugasan --}}
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat kuning"><x-ikon nama="tugas" /></span> Rincian Penugasan</h2>
            <p>Perbarui judul proyek, batas waktu (tenggat), atau deskripsi instruksi.</p>
        </div>
        <div class="form-bagian-isi">
            <label>Judul Tugas / Proyek <span class="teks-wajib" style="color:var(--gagal)">*</span>
                <input type="text" name="judul" value="{{ old('judul', $tugas->judul) }}" maxlength="180" placeholder="mis. Pengembangan Fitur Modul C" required class="@error('judul') salah @enderror">
                @error('judul')<span class="pesan-salah">{{ $message }}</span>@enderror
            </label>

            <div class="dua-kolom">
                <label>Tenggat Waktu <span class="teks-wajib" style="color:var(--gagal)">*</span>
                    <input type="date" name="tenggat" value="{{ old('tenggat', $tugas->tenggat?->format('Y-m-d')) }}" required class="@error('tenggat') salah @enderror">
                    @error('tenggat')<span class="pesan-salah">{{ $message }}</span>@enderror
                    <small class="redup">Ubah batas tanggal pengumpulan tugas bila diperlukan perpanjangan waktu.</small>
                </label>
                <label>Status Tugas Saat Ini
                    <input type="text" value="{{ $tugas->status }}" disabled>
                </label>
            </div>

            <label>Deskripsi Tugas <span class="teks-wajib" style="color:var(--gagal)">*</span>
                <textarea name="deskripsi" rows="5" placeholder="Jelaskan instruksi, tujuan, dan kriteria tugas ini secara rinci..." required class="@error('deskripsi') salah @enderror">{{ old('deskripsi', $tugas->deskripsi) }}</textarea>
                @error('deskripsi')<span class="pesan-salah">{{ $message }}</span>@enderror
            </label>

            @if ($tugas->judul_progres)
                <div style="margin-top: 10px; padding: 12px 16px; background: var(--permukaan-2); border-radius: var(--radius-kecil); border: 1px solid var(--garis);">
                    <b style="font-size: 13.5px; display: block; margin-bottom: 6px;">Tahapan Progres ({{ $tugas->jumlah_progres }} Tahapan Terdaftar):</b>
                    <ol style="margin: 0; padding-left: 20px; font-size: 13px; color: var(--teks);">
                        @foreach ($tugas->judul_progres as $j)
                            <li>{{ $j }}</li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>
    </div>

    {{-- Kaki Formulir --}}
    <div class="form-kaki">
        <a class="btn btn-halus" href="{{ route('mentor.tugas.show', $tugas) }}">Batal</a>
        <button class="btn btn-kuning" type="submit">
            <x-ikon nama="centang-saja" /> Simpan Perubahan
        </button>
    </div>
</form>
@endsection
