@extends('layouts.app')

@section('judul', 'Perbaikan Pendaftaran — ' . $pengajuan->kode_pengajuan)

@section('konten')
<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('peserta.pendaftaran.index') }}"><x-ikon nama="kembali" /> Kembali ke status pendaftaran</a>
        <h1>Perbaikan Pendaftaran Magang</h1>
        <p class="sub-judul">Periksa catatan dari tim penilai di bawah, perbarui data diri Anda, dan/atau unggah ulang dokumen yang perlu diperbaiki.</p>
    </div>
</div>

{{-- Catatan Perbaikan Superadmin --}}
<div class="alert alert-gagal" style="margin-bottom: 20px;">
    <x-ikon nama="peringatan" />
    <div>
        <b style="font-size: 15px;">Catatan Perbaikan dari Superadmin:</b>
        <p style="margin: 6px 0 0; white-space: pre-line;">{{ $pengajuan->catatan_perbaikan }}</p>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-gagal" style="margin-bottom: 20px;">
        <x-ikon nama="peringatan" />
        <div>
            <b>Data atau berkas perbaikan belum sesuai:</b>
            <ul style="margin: 6px 0 0 18px; padding: 0;">
                @foreach (array_unique($errors->all()) as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@php
    $calon = $pengajuan->calonPeserta->first();
@endphp

<form class="kartu form form-kartu" method="POST" action="{{ route('peserta.pendaftaran.update', $pengajuan) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- 01. Informasi Pengajuan --}}
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat"><x-ikon nama="koper" /></span> Pengajuan Magang</h2>
            <p>Detail program dan kode pengajuan yang sedang diperbaiki.</p>
        </div>
        <div class="form-bagian-isi">
            <div class="dua-kolom">
                <label>Kode Pengajuan
                    <input type="text" value="{{ $pengajuan->kode_pengajuan }}" disabled>
                </label>
                <label>Program Magang
                    <input type="text" value="{{ $pengajuan->program?->nama_program }}" disabled>
                </label>
            </div>
        </div>
    </div>

    {{-- 02. Data Diri Calon Peserta --}}
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat kuning"><x-ikon nama="pengguna" /></span> Data Diri Calon Peserta</h2>
            <p>Perbarui data diri Anda bila terdapat ketidaksesuaian.</p>
        </div>
        <div class="form-bagian-isi">
            <label>Nama lengkap <span class="teks-wajib" style="color:var(--gagal)">*</span>
                <input type="text" name="nama_lengkap" class="@error('nama_lengkap') salah @enderror" value="{{ old('nama_lengkap', $calon?->nama_lengkap) }}" maxlength="150" required>
                @error('nama_lengkap') <span class="pesan-salah">{{ $message }}</span> @enderror
            </label>

            <div class="dua-kolom">
                <label>NIM / Nomor Induk Pendidikan <span class="teks-wajib" style="color:var(--gagal)">*</span>
                    <input type="text" name="nomor_induk_pendidikan" class="@error('nomor_induk_pendidikan') salah @enderror" value="{{ old('nomor_induk_pendidikan', $calon?->nomor_induk_pendidikan) }}" maxlength="40" required>
                    @error('nomor_induk_pendidikan') <span class="pesan-salah">{{ $message }}</span> @enderror
                </label>
                <label>Nomor HP / WhatsApp <span class="teks-wajib" style="color:var(--gagal)">*</span>
                    <input type="text" name="no_hp" class="@error('no_hp') salah @enderror" value="{{ old('no_hp', $calon?->no_hp) }}" maxlength="30" required>
                    @error('no_hp') <span class="pesan-salah">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="dua-kolom">
                <label>Institusi Asal <span class="teks-wajib" style="color:var(--gagal)">*</span>
                    <input type="text" name="institusi_asal" class="@error('institusi_asal') salah @enderror" value="{{ old('institusi_asal', $calon?->institusi_asal) }}" maxlength="180" required>
                    @error('institusi_asal') <span class="pesan-salah">{{ $message }}</span> @enderror
                </label>
                <label>Jurusan / Program Studi <span class="teks-wajib" style="color:var(--gagal)">*</span>
                    <input type="text" name="jurusan_prodi" class="@error('jurusan_prodi') salah @enderror" value="{{ old('jurusan_prodi', $calon?->jurusan_prodi) }}" maxlength="150" required>
                    @error('jurusan_prodi') <span class="pesan-salah">{{ $message }}</span> @enderror
                </label>
            </div>
        </div>
    </div>

    {{-- 03. Unggah Berkas Perbaikan --}}
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat olive"><x-ikon nama="berkas" /></span> Unggah Berkas Perbaikan</h2>
            <p>Unggah berkas baru untuk mengganti berkas yang berstatus Tidak Sesuai. Berkas yang tidak diganti dapat dikosongkan.</p>
        </div>
        <div class="form-bagian-isi">
            @foreach ($pengajuan->program?->syaratDokumenProgram ?? [] as $syarat)
                @php
                    $namaInput = "dokumen.{$syarat->id_syarat}";
                    $dokumenLama = $pengajuan->dokumenPengajuan->firstWhere('id_syarat', $syarat->id_syarat);
                    $isFoto = \App\Models\DokumenPengajuan::isSyaratFoto($syarat->nama_syarat);
                    $accept = $isFoto ? '.jpg,.jpeg,.png' : '.pdf';
                    $petunjukFormat = \App\Models\DokumenPengajuan::getDeskripsiFormat($syarat->nama_syarat);
                @endphp

                <div style="padding: 14px; background: var(--permukaan-2); border-radius: 10px; margin-bottom: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <div>
                            <b>{{ $syarat->nama_syarat }}</b>
                            @if ($syarat->wajib) <span class="teks-wajib" style="color:var(--gagal)">*</span> @endif
                        </div>
                        @if ($dokumenLama)
                            @if ($dokumenLama->hasil_pemeriksaan === 'Tidak Sesuai')
                                <span class="status status-gagal">Tidak Sesuai</span>
                            @elseif ($dokumenLama->hasil_pemeriksaan === 'Sesuai')
                                <span class="status status-sukses">Sesuai</span>
                            @else
                                <span class="status status-netral">Belum Diperiksa</span>
                            @endif
                        @else
                            <span class="status status-peringatan">Belum Diunggah</span>
                        @endif
                    </div>

                    @if ($dokumenLama)
                        <div style="font-size: 13px; margin-bottom: 8px;">
                            <span class="redup">Berkas saat ini:</span>
                            <a class="tautan" href="{{ route('pengajuan.dokumen.unduh', $dokumenLama) }}">
                                <x-ikon nama="berkas" /> {{ $dokumenLama->nama_berkas }}
                            </a>
                            @if ($dokumenLama->catatan_pemeriksaan)
                                <p style="margin: 4px 0 0; color: var(--gagal);">
                                    <b>Catatan verifikator:</b> {{ $dokumenLama->catatan_pemeriksaan }}
                                </p>
                            @endif
                        </div>
                    @endif

                    <label style="margin-bottom: 0;">Ganti berkas ini:
                        <input type="file"
                               name="dokumen[{{ $syarat->id_syarat }}]"
                               accept="{{ $accept }}"
                               class="@error($namaInput) salah @enderror">
                        <small class="redup">Kosongkan jika tidak mengganti. Format {{ $petunjukFormat }}</small>
                        @error($namaInput) <span class="pesan-salah">{{ $message }}</span> @enderror
                    </label>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Kaki Formulir --}}
    <div class="form-kaki">
        <a class="btn btn-halus" href="{{ route('peserta.pendaftaran.index') }}">Batal</a>
        <button type="submit" class="btn btn-kuning">
            <x-ikon nama="centang-saja" /> Kirim Perbaikan
        </button>
    </div>
</form>
@endsection
