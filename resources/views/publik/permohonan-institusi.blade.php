@extends('layouts.guest')

@section('judul', 'Permohonan Magang Institusi — DTI Universitas Andalas')
@section('kelas-body', 'guest-lp')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    <link rel="stylesheet" href="{{ asset('css/permohonan.css') }}">
@endpush

@section('nav-kanan')
    <a class="tautan" href="#alur">Alur</a>
    <a class="tautan" href="#siapkan">Persiapan</a>
    <a class="tautan" href="{{ route('publik.permohonan.cek') }}">Cek Status</a>
    <a class="btn" href="#formulir">Isi Formulir</a>
@endsection

@section('footer-extra')
    <div class="publik-footer">
        <div class="publik-footer-identitas">
            <img src="{{ asset('images/logo-unand.png') }}" alt="Lambang Universitas Andalas" loading="lazy">
            <div>
                <p class="publik-footer-merek">DTI <b>Magang</b></p>
                <p class="publik-footer-sub">Sistem Pengelolaan Magang Direktorat Teknologi Informasi Universitas Andalas.</p>
            </div>
        </div>
        <nav class="publik-footer-kolom" aria-label="Jelajah halaman">
            <p class="publik-footer-judul">Jalur Institusi</p>
            <a href="#alur">Alur permohonan</a>
            <a href="#siapkan">Yang perlu disiapkan</a>
            <a href="#formulir">Isi formulir</a>
            <a href="{{ route('publik.permohonan.cek') }}">Cek status permohonan</a>
        </nav>
        <nav class="publik-footer-kolom" aria-label="Akses aplikasi">
            <p class="publik-footer-judul">Akses</p>
            <a href="{{ route('informasi-program') }}">Program magang perorangan</a>
            @auth
                <a href="{{ route('dashboard') }}">Buka Dashboard</a>
            @else
                <a href="{{ route('login') }}">Masuk ke Sistem</a>
            @endauth
        </nav>
    </div>
@endsection

@section('konten')
<div class="publik">

    {{-- 01 · Hero --}}
    <section class="publik-bagian publik-bagian--gelap publik-hero" id="atas">
        <div class="publik-wadah publik-hero-grid">
            <div class="publik-hero-teks">
                <p class="publik-kicker">Jalur Institusi · DTI Universitas Andalas</p>
                <h1 class="publik-judul">Ajukan magang untuk mahasiswa <em class="publik-aksen">institusi Anda</em></h1>
                <p class="publik-lead">Kampus atau sekolah dapat mengajukan permohonan magang resmi ke Direktorat Teknologi Informasi Universitas Andalas secara online — cukup isi satu formulir, satu permohonan bisa memuat beberapa peserta sekaligus.</p>
                <div class="publik-aksi">
                    <a class="btn btn-kuning" href="#formulir">Isi Formulir Permohonan <x-ikon nama="panah-kanan" /></a>
                    <a class="publik-tautan-tenang" href="{{ route('publik.permohonan.cek') }}">Sudah mengajukan? Cek status →</a>
                </div>
                <p class="publik-mono-baris">&gt; tanpa akun, ±5 menit pengisian<span class="publik-kursor" aria-hidden="true"></span></p>
            </div>
            <aside class="publik-panel" aria-label="Ringkasan jalur institusi" id="siapkan">
                <div class="publik-panel-kepala">
                    <span class="publik-panel-angka">3</span>
                    <span>hal yang perlu<br>disiapkan</span>
                </div>
                <ul class="publik-panel-daftar">
                    <li><x-ikon nama="berkas" /><span><b>Surat pengajuan resmi</b><small>Bernomor & bertanggal · PDF/JPG/PNG · maks. {{ $maksKb / 1024 }} MB</small></span></li>
                    <li><x-ikon nama="pengguna" /><span><b>Data peserta</b><small>Nama, NIM/NIS, jurusan/prodi (maks. {{ $maksPeserta }} orang)</small></span></li>
                    <li><x-ikon nama="surel" /><span><b>Narahubung institusi</b><small>Nama, email, dan nomor HP/WA aktif</small></span></li>
                </ul>
                <div class="publik-panel-fitur">
                    <span><x-ikon nama="papan-centang" />Tanpa akun</span>
                    <span><x-ikon nama="tambah-orang" />Banyak peserta</span>
                    <span><x-ikon nama="perisai" />Berkas aman</span>
                    <span><x-ikon nama="lonceng" />Dikabari DTI</span>
                </div>
            </aside>
        </div>
    </section>

    {{-- 02 · Alur --}}
    <section class="publik-bagian publik-bagian--terang" id="alur">
        <span class="publik-simpul" aria-hidden="true"></span>
        <div class="publik-wadah publik-reveal">
            <p class="publik-label">Alur permohonan</p>
            <h2 class="publik-h2">Dari formulir sampai <em class="publik-aksen">keputusan</em>.</h2>
            <ol class="publik-fase">
                <li><strong>Isi formulir</strong><span>Data institusi, peserta, dan scan surat dikirim online</span></li>
                <li><strong>Pemeriksaan</strong><span>Superadmin DTI memeriksa kelengkapan berkas</span></li>
                <li><strong>Keputusan</strong><span>Rekomendasi Kepala Seksi dan keputusan Direktur</span></li>
                <li><strong>Pemberitahuan</strong><span>Hasil disampaikan ke narahubung institusi</span></li>
            </ol>
        </div>
    </section>

    {{-- 03 · Formulir --}}
    <section class="publik-bagian publik-bagian--terang inst-bagian-form" id="formulir">
        <div class="publik-wadah">
            <form class="inst-form" method="POST" action="{{ route('publik.permohonan.store') }}" enctype="multipart/form-data" novalidate>
                @csrf

                <header class="inst-kartu inst-kepala">
                    <p class="publik-label">Formulir online</p>
                    <h2>Formulir Permohonan Magang Institusi</h2>
                    <p>Isi sesuai surat pengajuan yang ditandatangani pimpinan institusi. Setelah terkirim, Anda akan menerima <b>kode permohonan</b> sebagai bukti.</p>
                    <p class="inst-wajib-ket"><span class="wajib">*</span> Wajib diisi</p>
                </header>

                @if (session('gagal') || $errors->any())
                    <div class="alert alert-gagal inst-alert" role="alert">
                        <x-ikon nama="peringatan" />
                        <div>
                            <b>{{ session('gagal', \App\Services\PermohonanInstitusiService::PESAN_TIDAK_VALID) }}</b>
                            @if ($errors->any())
                                <span class="kecil">Periksa {{ count($errors->keys()) }} kolom yang ditandai merah di bawah.</span>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Honeypot anti-bot: disembunyikan dari manusia & pembaca layar --}}
                <div class="inst-madu" aria-hidden="true">
                    <label>Situs web <input type="text" name="situs_web" tabindex="-1" autocomplete="off"></label>
                </div>

                {{-- Bagian 1 --}}
                <fieldset class="inst-kartu">
                    <legend class="inst-judul-bagian"><span class="inst-no">1</span> Data institusi</legend>
                    <label>Nama institusi <span class="wajib">*</span>
                        <input name="nama_institusi" value="{{ old('nama_institusi') }}" maxlength="180" placeholder="mis. Politeknik Negeri Padang" autocomplete="organization" @class(['salah' => $errors->has('nama_institusi')])>
                        @error('nama_institusi')<span class="pesan-salah">{{ $message }}</span>@enderror
                    </label>
                    <div class="dua-kolom">
                        <label>Nomor surat pengajuan <span class="wajib">*</span>
                            <input name="nomor_surat" value="{{ old('nomor_surat') }}" maxlength="100" placeholder="mis. 123/PL9/KM/2026" @class(['salah' => $errors->has('nomor_surat')])>
                            @error('nomor_surat')<span class="pesan-salah">{{ $message }}</span>@enderror
                        </label>
                        <label>Tanggal surat <span class="wajib">*</span>
                            <input type="date" name="tanggal_surat" value="{{ old('tanggal_surat') }}" max="{{ today()->toDateString() }}" @class(['salah' => $errors->has('tanggal_surat')])>
                            @error('tanggal_surat')<span class="pesan-salah">{{ $message }}</span>@enderror
                        </label>
                    </div>
                </fieldset>

                {{-- Bagian 2 --}}
                <fieldset class="inst-kartu">
                    <legend class="inst-judul-bagian"><span class="inst-no">2</span> Narahubung institusi</legend>
                    <p class="inst-ket">Orang yang akan dihubungi DTI terkait hasil permohonan, mis. dosen pembimbing atau bagian akademik.</p>
                    <label>Nama narahubung <span class="wajib">*</span>
                        <input name="kontak_nama" value="{{ old('kontak_nama') }}" maxlength="60" placeholder="mis. Dr. Hendra, M.Kom." autocomplete="name" @class(['salah' => $errors->has('kontak_nama')])>
                        @error('kontak_nama')<span class="pesan-salah">{{ $message }}</span>@enderror
                    </label>
                    <div class="dua-kolom">
                        <label>Email <span class="wajib">*</span>
                            <input type="email" name="kontak_email" value="{{ old('kontak_email') }}" maxlength="60" placeholder="akademik@institusi.ac.id" autocomplete="email" @class(['salah' => $errors->has('kontak_email')])>
                            @error('kontak_email')<span class="pesan-salah">{{ $message }}</span>@enderror
                        </label>
                        <label>No. HP/WA <span class="wajib">*</span>
                            <input type="tel" inputmode="tel" name="kontak_telepon" value="{{ old('kontak_telepon') }}" maxlength="20" placeholder="08xxxxxxxxxx" autocomplete="tel" @class(['salah' => $errors->has('kontak_telepon')])>
                            @error('kontak_telepon')<span class="pesan-salah">{{ $message }}</span>@enderror
                        </label>
                    </div>
                </fieldset>

                {{-- Bagian 3 --}}
                <fieldset class="inst-kartu">
                    <legend class="inst-judul-bagian"><span class="inst-no">3</span> Data peserta</legend>
                    <p class="inst-ket">Satu baris untuk satu peserta, sesuai nama yang tercantum di surat. Klik <b>Tambah peserta</b> untuk menambah baris.</p>
                    @include('permohonan._tabel-peserta', ['maksPeserta' => $maksPeserta])
                </fieldset>

                {{-- Bagian 4 --}}
                <fieldset class="inst-kartu">
                    <legend class="inst-judul-bagian"><span class="inst-no">4</span> Surat pengajuan</legend>
                    <label>Scan surat pengajuan resmi <span class="wajib">*</span>
                        <input type="file" name="surat" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                               data-cek-berkas="info-surat" data-maks-kb="{{ $maksKb }}" @class(['salah' => $errors->has('surat')])>
                        <small class="petunjuk" id="info-surat">PDF, JPG, atau PNG · maksimal {{ $maksKb / 1024 }} MB</small>
                        @error('surat')<span class="pesan-salah">{{ $message }}</span>@enderror
                        @if ($errors->any() && ! $errors->has('surat'))
                            <span class="petunjuk">Demi keamanan, berkas surat perlu dipilih ulang.</span>
                        @endif
                    </label>
                </fieldset>

                {{-- Kirim --}}
                <div class="inst-kartu inst-kirim">
                    <label class="inst-centang">
                        <input type="checkbox" name="pernyataan" value="1" @checked(old('pernyataan'))>
                        <span>Saya menyatakan data yang diisi sesuai dengan surat pengajuan resmi institusi dan dapat dipertanggungjawabkan. <span class="wajib">*</span></span>
                    </label>
                    @error('pernyataan')<span class="pesan-salah">{{ $message }}</span>@enderror
                    <div class="inst-kirim-aksi">
                        <button class="btn">Kirim Permohonan <x-ikon nama="panah-kanan" /></button>
                        <span class="petunjuk">Pastikan data sudah benar — permohonan yang terkirim tidak bisa diubah sendiri.</span>
                    </div>
                </div>
            </form>
        </div>
    </section>

</div>
@endsection
