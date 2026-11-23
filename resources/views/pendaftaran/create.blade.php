@extends('layouts.guest')

@section('judul', 'Pendaftaran ' . $program->nama_program . ' — ' . config('app.name'))

@section('konten')
<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('informasi-program') }}"><x-ikon nama="kembali" /> Kembali ke informasi program</a>
        <h1>Formulir Pendaftaran Magang</h1>
        <p class="sub-judul">Silakan lengkapi data akun, data diri, dan unggah dokumen persyaratan untuk mendaftar pada program ini.</p>
    </div>
</div>

@if (session('gagal'))
    <div class="alert alert-gagal" style="margin-bottom: 20px;">
        <x-ikon nama="peringatan" />
        <div>{{ session('gagal') }}</div>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-gagal" style="margin-bottom: 20px;">
        <x-ikon nama="peringatan" />
        <div>
            <b>Data atau dokumen belum lengkap:</b>
            <ul style="margin: 6px 0 0 18px; padding: 0;">
                @foreach (array_unique($errors->all()) as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<form class="kartu form form-kartu" method="POST" action="{{ route('pendaftaran.store', $program) }}" enctype="multipart/form-data">
    @csrf

    {{-- 01. Informasi Program (Read-only) --}}
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat"><x-ikon nama="koper" /></span> Program Magang</h2>
            <p>Program yang Anda pilih untuk pendaftaran ini.</p>
        </div>
        <div class="form-bagian-isi">
            <label>Nama program
                <input type="text" value="{{ $program->nama_program }}" disabled>
            </label>
            <div class="dua-kolom">
                <label>Periode pendaftaran
                    <input type="text" value="{{ $program->tanggal_buka?->translatedFormat('d M Y') }} s.d. {{ $program->tanggal_tutup?->translatedFormat('d M Y') }}" disabled>
                </label>
                <label>Status
                    <input type="text" value="Sedang Dibuka" disabled>
                </label>
            </div>
            @if ($program->informasi)
                <label>Deskripsi program
                    <textarea rows="2" disabled>{{ $program->informasi }}</textarea>
                </label>
            @endif
        </div>
    </div>

    {{-- 02. Data Akun --}}
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat kuning"><x-ikon nama="pengguna" /></span> Data Akun</h2>
            <p>Akun ini akan dibuat otomatis dan digunakan untuk masuk ke sistem.</p>
        </div>
        <div class="form-bagian-isi">
            <label>Nama lengkap (sesuai identitas) <span class="teks-wajib" style="color:var(--gagal)">*</span>
                <input type="text" name="nama_lengkap" class="@error('nama_lengkap') salah @enderror" value="{{ old('nama_lengkap') }}" maxlength="150" placeholder="mis. Rani Putri" required>
                @error('nama_lengkap') <span class="pesan-salah">{{ $message }}</span> @enderror
            </label>

            <label>Alamat email aktif <span class="teks-wajib" style="color:var(--gagal)">*</span>
                <span class="input-ikon">
                    <x-ikon nama="surel" />
                    <input type="email" name="email" class="@error('email') salah @enderror" value="{{ old('email') }}" maxlength="191" placeholder="nama@email.com" required>
                </span>
                @error('email') <span class="pesan-salah">{{ $message }}</span> @enderror
                <small class="redup">Gunakan email aktif untuk menerima informasi akun dan pendaftaran.</small>
            </label>

            <div class="dua-kolom">
                <label>Kata sandi <span class="teks-wajib" style="color:var(--gagal)">*</span>
                    <span class="input-ikon">
                        <x-ikon nama="kunci" />
                        <input type="password" name="password" class="@error('password') salah @enderror" placeholder="Minimal 8 karakter" required>
                    </span>
                    @error('password') <span class="pesan-salah">{{ $message }}</span> @enderror
                </label>
                <label>Konfirmasi kata sandi <span class="teks-wajib" style="color:var(--gagal)">*</span>
                    <span class="input-ikon">
                        <x-ikon nama="kunci" />
                        <input type="password" name="password_confirmation" class="@error('password_confirmation') salah @enderror" placeholder="Ulangi kata sandi" required>
                    </span>
                    @error('password_confirmation') <span class="pesan-salah">{{ $message }}</span> @enderror
                </label>
            </div>
        </div>
    </div>

    {{-- 03. Data Diri Calon Peserta --}}
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat olive"><x-ikon nama="papan-centang" /></span> Data Diri Calon Peserta</h2>
            <p>Informasi pendidikan dan kontak yang dapat dihubungi.</p>
        </div>
        <div class="form-bagian-isi">
            <div class="dua-kolom">
                <label>NIM / Nomor Induk Pendidikan <span class="teks-wajib" style="color:var(--gagal)">*</span>
                    <input type="text" name="nomor_induk_pendidikan" class="@error('nomor_induk_pendidikan') salah @enderror" value="{{ old('nomor_induk_pendidikan') }}" maxlength="40" placeholder="mis. 2311521001" required>
                    @error('nomor_induk_pendidikan') <span class="pesan-salah">{{ $message }}</span> @enderror
                </label>
                <label>Nomor HP / WhatsApp <span class="teks-wajib" style="color:var(--gagal)">*</span>
                    <input type="text" name="no_hp" class="@error('no_hp') salah @enderror" value="{{ old('no_hp') }}" maxlength="30" placeholder="mis. 081234567890" required>
                    @error('no_hp') <span class="pesan-salah">{{ $message }}</span> @enderror
                </label>
            </div>
            <div class="dua-kolom">
                <label>Institusi Asal <span class="teks-wajib" style="color:var(--gagal)">*</span>
                    <input type="text" name="institusi_asal" class="@error('institusi_asal') salah @enderror" value="{{ old('institusi_asal') }}" maxlength="180" placeholder="mis. Universitas Andalas" required>
                    @error('institusi_asal') <span class="pesan-salah">{{ $message }}</span> @enderror
                </label>
                <label>Jurusan / Program Studi <span class="teks-wajib" style="color:var(--gagal)">*</span>
                    <input type="text" name="jurusan_prodi" class="@error('jurusan_prodi') salah @enderror" value="{{ old('jurusan_prodi') }}" maxlength="150" placeholder="mis. Sistem Informasi" required>
                    @error('jurusan_prodi') <span class="pesan-salah">{{ $message }}</span> @enderror
                </label>
            </div>
        </div>
    </div>

    {{-- 04. Dokumen Persyaratan --}}
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat"><x-ikon nama="berkas" /></span> Dokumen Persyaratan</h2>
            <p>Unggah berkas sesuai ketentuan format dan ukuran masing-masing syarat.</p>
        </div>
        <div class="form-bagian-isi">
            @if ($program->syaratDokumenProgram->isEmpty())
                <p class="redup">Program ini tidak memerlukan dokumen persyaratan tambahan.</p>
            @else
                @foreach ($program->syaratDokumenProgram as $syarat)
                    @php
                        $namaInput = "dokumen.{$syarat->id_syarat}";
                        $isFoto = \App\Models\DokumenPengajuan::isSyaratFoto($syarat->nama_syarat);
                        $accept = $isFoto ? '.jpg,.jpeg,.png' : '.pdf';
                        $petunjukFormat = \App\Models\DokumenPengajuan::getDeskripsiFormat($syarat->nama_syarat);
                    @endphp
                    <label>{{ $syarat->nama_syarat }} @if ($syarat->wajib)<span class="teks-wajib" style="color:var(--gagal)">*</span>@endif
                        <input type="file"
                               name="dokumen[{{ $syarat->id_syarat }}]"
                               accept="{{ $accept }}"
                               class="@error($namaInput) salah @enderror"
                               @if ($syarat->wajib) required @endif>
                        <small class="redup">{{ $syarat->wajib ? 'Wajib' : 'Opsional' }} · Format {{ $petunjukFormat }}</small>
                        @error($namaInput) <span class="pesan-salah">{{ $message }}</span> @enderror
                    </label>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Kaki Formulir --}}
    <div class="form-kaki">
        <a class="btn btn-halus" href="{{ route('informasi-program') }}">Batal</a>
        <button type="submit" class="btn btn-kuning">
            <x-ikon nama="centang-saja" /> Kirim Pendaftaran
        </button>
    </div>
</form>
@endsection
