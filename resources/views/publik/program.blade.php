@extends('layouts.guest')

@section('judul', 'Program Magang DTI — Direktorat Teknologi Informasi Universitas Andalas')
@section('kelas-body', 'guest-lp')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
@endpush

@section('nav-kanan')
    <a class="tautan" href="#program">Program</a>
    <a class="tautan" href="#bidang">Bidang</a>
    <a class="tautan" href="#alur">Alur</a>
    @auth
        <a class="btn btn-garis" href="{{ route('dashboard') }}">Dashboard</a>
    @else
        <a class="btn" href="{{ route('login') }}">Login</a>
    @endauth
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
            <p class="publik-footer-judul">Jelajah</p>
            <a href="#program">Program dibuka</a>
            <a href="#bidang">Tiga bidang</a>
            <a href="#alur">Alur magang</a>
        </nav>
        <nav class="publik-footer-kolom" aria-label="Akses aplikasi">
            <p class="publik-footer-judul">Akses</p>
            @auth
                <a href="{{ route('dashboard') }}">Buka Dashboard</a>
            @else
                <a href="{{ route('login') }}">Masuk ke Sistem</a>
            @endauth
            <a href="{{ route('informasi-program') }}">Program Magang</a>
        </nav>
    </div>
@endsection

@section('konten')
<div class="publik">

    {{-- 01 · Hero --}}
    <section class="publik-bagian publik-bagian--gelap publik-hero" id="atas">
        <div class="publik-wadah publik-hero-grid">
            <div class="publik-hero-teks">
                <p class="publik-kicker">Direktorat Teknologi Informasi · Universitas Andalas</p>
                <h1 class="publik-judul">Program Magang DTI yang <em class="publik-aksen">sedang</em> dibuka</h1>
                <p class="publik-lead">Bergabunglah dalam magang di lingkungan Direktorat Teknologi Informasi Universitas Andalas — dari pendaftaran hingga sertifikat, semuanya dalam satu sistem.</p>
                <div class="publik-aksi">
                    @auth
                        <a class="btn btn-kuning" href="{{ route('dashboard') }}">Buka Dashboard</a>
                    @else
                        <a class="btn btn-kuning" href="{{ route('login') }}">Masuk ke Sistem</a>
                    @endauth
                    <a class="publik-tautan-tenang" href="#program">Lihat program yang dibuka ↓</a>
                </div>
                <p class="publik-mono-baris">&gt; tiga bidang menanti kontribusimu<span class="publik-kursor" aria-hidden="true"></span></p>
            </div>
        </div>
    </section>

    {{-- 02 · Program dibuka --}}
    <section class="publik-bagian publik-bagian--terang" id="program">
        <span class="publik-simpul" aria-hidden="true"></span>
        <div class="publik-wadah publik-reveal">
            <h2 class="publik-h2">Pilih programnya, siapkan <em class="publik-aksen">berkasnya</em>.</h2>

            <div class="publik-grid">
                @forelse ($program as $p)
                    <article class="publik-kartu">
                        <h3>{{ $p->nama_program }}</h3>
                        <p class="publik-kartu-waktu"><b>Pendaftaran:</b> {{ $p->tanggal_buka->translatedFormat('d M Y') }} – {{ $p->tanggal_tutup->translatedFormat('d M Y') }}</p>
                        @if ($p->informasi)<p class="publik-kartu-info">{{ $p->informasi }}</p>@endif
                        <details class="publik-syarat">
                            <summary>Lihat persyaratan</summary>
                            <div class="publik-syarat-isi">
                                @if ($p->syaratDokumenProgram->isNotEmpty())
                                    <ul>
                                        @foreach ($p->syaratDokumenProgram->sortBy('urutan') as $syarat)
                                            <li>{{ $syarat->nama_syarat }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                <p class="publik-catatan">Pendaftaran online sedang dalam pengembangan.</p>
                            </div>
                        </details>
                    </article>
                @empty
                    <div class="publik-kosong">
                        <svg class="ikon-besar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8h16v11H4zM9 8V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M4 13h16"/></svg>
                        <b>Belum ada program rekrutmen yang dibuka</b>
                        Silakan kembali lagi nanti untuk melihat program magang terbaru.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- 03 · Tiga bidang --}}
    <section class="publik-bagian publik-bagian--gelap" id="bidang">
        <svg class="publik-gelombang" viewBox="0 0 1440 220" preserveAspectRatio="none" aria-hidden="true" fill="none">
            <path d="M0 180 C 240 118, 480 212, 720 160 C 960 108, 1200 192, 1440 128" stroke="var(--kuning)" stroke-opacity=".16" stroke-width="1.5"/>
            <path d="M0 212 C 260 158, 520 238, 780 190 C 1040 142, 1240 212, 1440 166" stroke="#ffffff" stroke-opacity=".07" stroke-width="1.5"/>
        </svg>
        <div class="publik-wadah publik-reveal">
            <h2 class="publik-pernyataan">Tiga bidang, <em class="publik-aksen">satu</em> pengalaman magang.</h2>

            <ol class="publik-stasiun">
                <li>
                    <span class="publik-node" aria-hidden="true"></span>
                    <div class="publik-stasiun-teks">
                        <span class="publik-stasiun-no">Programming</span>
                        <h3>Membangun perangkat lunak</h3>
                        <p>Merancang, membangun, dan merawat aplikasi yang digunakan di lingkungan Direktorat Teknologi Informasi.</p>
                    </div>
                    <svg class="publik-motif" viewBox="0 0 160 56" aria-hidden="true" fill="none">
                        <path d="M4 30 C 26 12, 48 48, 72 30 C 88 18, 96 40, 108 30" stroke="var(--kuning)" stroke-opacity=".55" stroke-width="1.5" stroke-linecap="round"/>
                        <text x="118" y="38" class="publik-mono-teks">{ }</text>
                    </svg>
                </li>
                <li>
                    <span class="publik-node" aria-hidden="true"></span>
                    <div class="publik-stasiun-teks">
                        <span class="publik-stasiun-no">Multimedia</span>
                        <h3>Mengolah konten visual</h3>
                        <p>Mengerjakan kebutuhan desain, foto, video, dan publikasi digital untuk komunikasi Direktorat.</p>
                    </div>
                    <svg class="publik-motif" viewBox="0 0 160 56" aria-hidden="true" fill="none">
                        <path d="M8 20 V36 M22 10 V46 M36 18 V38 M50 4 V52 M64 14 V42 M78 8 V48 M92 18 V38 M106 12 V44 M120 22 V34 M134 16 V40 M148 24 V32" stroke="var(--kuning)" stroke-opacity=".8" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                </li>
                <li>
                    <span class="publik-node" aria-hidden="true"></span>
                    <div class="publik-stasiun-teks">
                        <span class="publik-stasiun-no">Jaringan</span>
                        <h3>Menjaga konektivitas</h3>
                        <p>Membantu pengelolaan infrastruktur dan konektivitas jaringan agar layanan digital tetap berjalan.</p>
                    </div>
                    <svg class="publik-motif" viewBox="0 0 160 56" aria-hidden="true" fill="none">
                        <path d="M22 40 L62 12 L104 30 L140 10 M104 30 L120 46 L70 46 L22 40 M62 12 L70 46" stroke="var(--kuning)" stroke-opacity=".45" stroke-width="1.5" stroke-linecap="round"/>
                        <circle cx="22" cy="40" r="4" fill="var(--kuning)"/>
                        <circle cx="62" cy="12" r="4" fill="var(--kuning)"/>
                        <circle cx="104" cy="30" r="4" fill="var(--kuning)"/>
                        <circle cx="140" cy="10" r="4" fill="var(--kuning)"/>
                        <circle cx="120" cy="46" r="4" fill="var(--kuning)"/>
                        <circle cx="70" cy="46" r="4" fill="var(--kuning)"/>
                    </svg>
                </li>
            </ol>
        </div>
    </section>

    {{-- 04 · Alur magang --}}
    <section class="publik-bagian publik-bagian--terang" id="alur">
        <span class="publik-simpul" aria-hidden="true"></span>
        <div class="publik-wadah publik-reveal">
            <h2 class="publik-h2">Dua pintu masuk, <em class="publik-aksen">satu</em> proses yang sama.</h2>

            <div class="publik-pintu">
                <div class="publik-jalur">
                    <h3>Perorangan — Rekrutmen DTI</h3>
                    <p>Calon peserta mengajukan magang secara mandiri pada program rekrutmen yang sedang dibuka.</p>
                </div>
                <div class="publik-jalur">
                    <h3>Institusi — Permohonan Surat</h3>
                    <p>Institusi mengajukan permohonan resmi melalui surat, yang dicatat oleh Direktorat; satu permohonan dapat memuat beberapa calon.</p>
                </div>
            </div>

            <svg class="publik-benang-merge" viewBox="0 0 1200 76" preserveAspectRatio="none" aria-hidden="true" fill="none">
                <path d="M300 0 V16 C300 48, 480 60, 600 66 M900 0 V16 C900 48, 720 60, 600 66 M600 66 V76" stroke="var(--peru)" stroke-width="1.5" stroke-opacity=".5" stroke-linecap="round"/>
            </svg>

            <ol class="publik-fase">
                <li><strong>Pemeriksaan</strong><span>Kelengkapan berkas diperiksa tim DTI</span></li>
                <li><strong>Keputusan</strong><span>Rekomendasi dan keputusan penerimaan</span></li>
                <li><strong>Pelaksanaan</strong><span>Penempatan, presensi, tugas, dan logbook</span></li>
                <li><strong>Penyelesaian</strong><span>Evaluasi akhir dan unggah sertifikat</span></li>
            </ol>
        </div>
    </section>

    {{-- 05 · Penutup --}}
    <section class="publik-bagian publik-bagian--gelap publik-penutup" id="mulai">
        <div class="publik-wadah publik-reveal">
            <h2 class="publik-h2">Langkah pertamamu dimulai <em class="publik-aksen">dari sini</em>.</h2>
            <p>Masuk ke sistem untuk melihat dashboard sesuai peranmu — atau kembali menimbang program yang tersedia di atas.</p>
            <span class="publik-lingkar">
                <span class="publik-lingkar-simpul kiri" aria-hidden="true"></span>
                <span class="publik-lingkar-simpul kanan" aria-hidden="true"></span>
                @auth
                    <a class="btn btn-kuning" href="{{ route('dashboard') }}">Buka Dashboard</a>
                @else
                    <a class="btn btn-kuning" href="{{ route('login') }}">Masuk ke Sistem</a>
                @endauth
            </span>
        </div>
    </section>

</div>
@endsection
