@extends('layouts.app')
@section('judul', $judul)
@section('konten')
@php
    // Pisahkan kartu bernilai angka dan bernilai teks (mis. "Aktif", "Belum").
    $numerik = array_filter($kartu, 'is_numeric');
    $teks = array_diff_key($kartu, $numerik);

    // Kartu utama: statistik aksi pertama (menunggu/belum/siap); jika tidak ada, statistik pertama.
    $kunciUtama = null;
    $adaAksi = false;
    foreach ($numerik as $label => $nilai) {
        if (preg_match('/menunggu|belum|siap/i', $label)) {
            $kunciUtama = $label;
            $adaAksi = true;
            break;
        }
    }
    if ($kunciUtama === null && $numerik !== []) {
        $kunciUtama = array_key_first($numerik);
    }
    $sisa = $kunciUtama !== null ? array_diff_key($numerik, [$kunciUtama => true]) : $numerik;
@endphp
@php
    // Ikon & warna kartu statistik ditebak dari labelnya.
    $gayaStat = function (string $label, int $i): array {
        $l = mb_strtolower($label);
        $ikon = match (true) {
            str_contains($l, 'pengajuan') || str_contains($l, 'keputusan') => 'papan-centang',
            str_contains($l, 'program') => 'koper',
            str_contains($l, 'sertifikat') => 'medali',
            str_contains($l, 'logbook') => 'buku',
            str_contains($l, 'tugas') || str_contains($l, 'progres') => 'tugas',
            str_contains($l, 'izin') || str_contains($l, 'hadir') || str_contains($l, 'presensi') => 'jam',
            str_contains($l, 'ditempatkan') => 'lokasi',
            str_contains($l, 'peserta') || str_contains($l, 'calon') => 'pengguna',
            default => 'grafik',
        };
        $varian = ['', 'stat--kuning', 'stat--peru', 'stat--olive'][$i % 4];
        return [$ikon, $varian];
    };
    $namaDepan = \Illuminate\Support\Str::of(auth()->user()->nama_lengkap ?? '')->explode(' ')->first();
    $jam = (int) now()->format('G');
    $salam = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
@endphp

<section class="sambutan">
    <div class="sambutan-teks">
        <p class="kicker">{{ $judul }}</p>
        <h1>{{ $salam }}, {{ $namaDepan }}</h1>
        <p>Berikut ringkasan aktivitas dan statistik terbaru Sistem Pengelolaan Magang DTI.</p>
        <div class="sambutan-meta">
            <span><x-ikon nama="perisai" />{{ auth()->user()->peran->nama_peran }}</span>
            <span><x-ikon nama="kalender" />{{ now()->translatedFormat('l, d F Y') }}</span>
        </div>
    </div>
    @if ($kunciUtama !== null)
        <div class="sambutan-sorot">
            <div class="label-kecil">{{ $adaAksi ? 'Perlu tindakan' : 'Prioritas utama' }}</div>
            <div class="angka-utama">{{ $numerik[$kunciUtama] }}</div>
            <div class="ket">{{ $kunciUtama }}</div>
        </div>
    @endif
</section>

@if ($teks)
    <div class="baris-status">
        @foreach ($teks as $label => $nilai)
            @php
                $kelasStatus = match (true) {
                    in_array($nilai, ['Aktif', 'Sudah', 'Selesai']) => 'status-sukses',
                    in_array($nilai, ['Belum', 'Menunggu']) => 'status-peringatan',
                    default => 'status-netral',
                };
            @endphp
            <span class="status {{ $kelasStatus }}">{{ $label }}: {{ $nilai }}</span>
        @endforeach
    </div>
@endif

@if ($sisa)
    <div class="statistik-grid">
        @foreach (array_keys($sisa) as $i => $label)
            @php [$ikon, $varian] = $gayaStat($label, $i); @endphp
            <div class="stat {{ $varian }}">
                <span class="ikon-bulat"><x-ikon :nama="$ikon" /></span>
                <div class="nilai">{{ $sisa[$label] }}</div>
                <div class="label">{{ $label }}</div>
            </div>
        @endforeach
    </div>
@endif

@isset($pengajuan)
    <div class="kartu">
        <div class="kartu-kepala">
            <div class="kartu-judul">
                <span class="ikon-bulat"><x-ikon nama="papan-centang" /></span>
                <div>
                    <h2>Pengajuan Saya</h2>
                    <p>Pantau status setiap pengajuan magang yang pernah Anda kirim.</p>
                </div>
            </div>
            <a class="btn btn-garis btn-kecil" href="{{ route('informasi-program') }}"><x-ikon nama="koper" /> Program dibuka</a>
        </div>
        @forelse ($pengajuan as $p)
            @php
                $kelasStatus = match ($p->status_pengajuan) {
                    'Diajukan' => 'status-peringatan',
                    'Lengkap'  => 'status-sukses',
                    'Perlu Perbaikan' => 'status-gagal',
                    default => 'status-netral',
                };
            @endphp
            <div class="notif">
                <div class="notif-isi">
                    <span class="inisial v{{ $loop->index % 5 }}"><x-ikon nama="berkas" /></span>
                    <div>
                        <b>{{ $p->program?->nama_program ?? 'Jalur Institusi' }}</b>
                        <small class="redup">Kode {{ $p->kode_pengajuan }}</small>
                    </div>
                </div>
                <span class="status {{ $kelasStatus }}">{{ $p->status_pengajuan }}</span>
            </div>
        @empty
            <div class="kosong">
                <div class="ilustrasi"><x-ikon nama="kotak-masuk" /></div>
                <b>Belum ada pengajuan</b>
                Lihat program magang yang sedang dibuka untuk mulai mendaftar.
                <br><a class="btn" href="{{ route('informasi-program') }}">Lihat Program Magang <x-ikon nama="panah-kanan" /></a>
            </div>
        @endforelse
    </div>
@endisset

@isset($peserta)
    <div class="kartu" style="margin-top: 20px; background: linear-gradient(135deg, rgba(37, 99, 235, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%); border: 1px solid rgba(37, 99, 235, 0.15);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <span class="ikon-bulat biru" style="width: 44px; height: 44px; font-size: 18px;"><x-ikon nama="lokasi" /></span>
                <div>
                    <span class="redup" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Penempatan Magang</span>
                    <h3 style="margin: 2px 0 4px; font-size: 16px;">{{ $peserta->seksi?->nama_seksi ?? 'Direktorat Teknologi Informasi' }}</h3>
                    <p style="margin: 0; font-size: 13px;" class="redup">
                        Mentor: <b>{{ $peserta->mentor?->nama_lengkap ?? '-' }}</b> · 
                        Periode: {{ $peserta->tanggal_mulai?->format('d M Y') }} s/d {{ $peserta->tanggal_selesai?->format('d M Y') }}
                    </p>
                </div>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('peserta.presensi.index') }}" class="btn btn-garis btn-kecil"><x-ikon nama="jam" /> Presensi</a>
                <a href="{{ route('peserta.logbook.index') }}" class="btn btn-garis btn-kecil"><x-ikon nama="buku" /> Logbook</a>
            </div>
        </div>
    </div>

    {{-- Informasi Tugas & Proyek Peserta --}}
    <div class="kartu" style="margin-top: 20px;">
        <div class="kartu-kepala">
            <div class="kartu-judul">
                <span class="ikon-bulat kuning"><x-ikon nama="tugas" /></span>
                <div>
                    <h2>Daftar Tugas &amp; Proyek Pengerjaan</h2>
                    <p>Pantau progres pengerjaan tugas, tenggat waktu, dan serahkan hasil pekerjaan kepada mentor.</p>
                </div>
            </div>
            <a class="btn btn-garis btn-kecil" href="{{ route('peserta.tugas.index') }}">
                <x-ikon nama="panah-kanan" /> Buka Halaman Tugas
            </a>
        </div>

        @if ($daftarTugas->isEmpty())
            <div class="kosong">
                <div class="ilustrasi"><x-ikon nama="tugas" /></div>
                <b>Belum Ada Tugas Diberikan</b>
                Mentor pembimbing Anda belum memberikan tugas. Silakan koordinasikan dengan mentor Anda.
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 8px;">
                @foreach ($daftarTugas as $t)
                    @php
                        $urutanAktif = $t->urutanProgresTerakhir();
                        $persen = $t->jumlah_progres > 0 ? min(100, round(($urutanAktif / $t->jumlah_progres) * 100)) : 0;
                        $kelasStatus = match ($t->status) {
                            'Diserahkan' => 'status-peringatan',
                            'Selesai' => 'status-sukses',
                            'Perlu Perbaikan' => 'status-gagal',
                            default => 'status-netral',
                        };
                        $hariSisa = now()->startOfDay()->diffInDays($t->tenggat?->startOfDay(), false);
                    @endphp
                    <div style="background: var(--permukaan-2); border: 1px solid var(--garis); border-radius: var(--radius-kecil); padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                        <div style="flex: 1; min-width: 250px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; flex-wrap: wrap;">
                                <span class="status {{ $kelasStatus }}" style="font-size: 11.5px;">{{ $t->status }}</span>
                                @if ($t->status === 'Selesai' && $t->nilai !== null)
                                    <span class="chip" style="font-size: 11.5px; font-weight: 700; color: var(--sukses); background: rgba(34, 197, 94, 0.12);">
                                        <x-ikon nama="medali" style="width: 12px; height: 12px; vertical-align: -1px;" /> Nilai: {{ $t->nilai }} / 100
                                    </span>
                                @endif
                                <span class="redup" style="font-size: 12px;">
                                    <x-ikon nama="kalender" style="width: 12px; height: 12px; vertical-align: -1px;" /> Tenggat: {{ $t->tenggat?->format('d M Y') }}
                                    @if ($t->status !== 'Selesai')
                                        @if ($hariSisa < 0)
                                            <b style="color: var(--gagal);">(Terlewat {{ abs($hariSisa) }} hari)</b>
                                        @elseif ($hariSisa === 0)
                                            <b style="color: var(--peringatan);">(Hari ini)</b>
                                        @else
                                            <span>(Sisa {{ $hariSisa }} hari)</span>
                                        @endif
                                    @endif
                                </span>
                            </div>
                            <b style="font-size: 15px; color: var(--teks); display: block; margin-bottom: 4px;">{{ $t->judul }}</b>
                            <p style="margin: 0; font-size: 13px; color: var(--redup); max-width: 550px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $t->deskripsi }}</p>

                            {{-- Progress Bar Mini --}}
                            <div style="margin-top: 10px; display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; max-width: 220px; height: 6px; background: rgba(0,0,0,0.08); border-radius: 999px; overflow: hidden;">
                                    <div style="width: {{ $persen }}%; height: 100%; background: {{ $t->status === 'Selesai' ? 'var(--sukses)' : 'var(--aksen)' }}; border-radius: 999px;"></div>
                                </div>
                                <span style="font-size: 11.5px; font-weight: 600; color: var(--redup);">
                                    Tahap {{ $urutanAktif }} dari {{ $t->jumlah_progres }} ({{ $persen }}%)
                                </span>
                            </div>
                        </div>

                        <div style="display: flex; gap: 8px; align-items: center;">
                            @if ($t->path_dokumen)
                                <a href="{{ route('tugas.dokumen', $t) }}" target="_blank" class="btn btn-garis btn-kecil" title="Lihat dokumen instruksi dari mentor">
                                    <x-ikon nama="berkas" /> Modul
                                </a>
                            @endif
                            <a href="{{ route('peserta.tugas.show', $t) }}" class="btn {{ $t->apakahBolehKirimProgres() ? 'btn-kuning' : 'btn-garis' }} btn-kecil">
                                @if ($t->status === 'Perlu Perbaikan')
                                    <x-ikon nama="ubah" /> Kirim Revisi
                                @elseif ($t->apakahBolehKirimProgres())
                                    <x-ikon nama="plus" /> Kirim Progres
                                @else
                                    <x-ikon nama="mata" /> Detail
                                @endif
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endisset
@endsection
