@extends('layouts.app')
@section('judul', 'Periksa Logbook')
@section('konten')
@php
    $statusKelas = ['Dikirim' => 'status-peringatan', 'Disetujui' => 'status-sukses', 'Perlu Perbaikan' => 'status-gagal'];
    $jumlahDisetujui = $terdahulu->where('status', 'Disetujui')->count();
    $jumlahPerbaikan = $terdahulu->where('status', 'Perlu Perbaikan')->count();
@endphp

<div class="kepala-halaman">
    <div>
        <p class="kicker">Pembimbingan · UC-12</p>
        <h1>Periksa Logbook</h1>
        <p class="sub-judul">Tinjau aktivitas harian yang dikirim peserta bimbingan dan berikan umpan balik pemeriksaan.</p>
    </div>
</div>

<div class="statistik-grid">
    <div class="stat stat--kuning">
        <span class="ikon-bulat"><x-ikon nama="jam" /></span>
        <div class="nilai">{{ $antrean->count() }}</div>
        <div class="label">Menunggu pemeriksaan</div>
    </div>
    <div class="stat">
        <span class="ikon-bulat"><x-ikon nama="centang" /></span>
        <div class="nilai">{{ $jumlahDisetujui }}</div>
        <div class="label">Logbook disetujui</div>
    </div>
    <div class="stat stat--peru">
        <span class="ikon-bulat"><x-ikon nama="peringatan" /></span>
        <div class="nilai">{{ $jumlahPerbaikan }}</div>
        <div class="label">Perlu perbaikan</div>
    </div>
</div>

<div class="kartu kartu-tabel">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat kuning"><x-ikon nama="jam" /></span>
            <div>
                <h2>Menunggu pemeriksaan</h2>
                <p>Logbook terbaru yang baru dikirim peserta dan membutuhkan tinjauan Anda.</p>
            </div>
        </div>
    </div>
    <div class="tabel-wadah">
        <table>
            <thead>
                <tr>
                    <th>Peserta</th>
                    <th>Tanggal Kegiatan</th>
                    <th>Waktu Kirim</th>
                    <th class="aksi-tabel">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($antrean as $l)
                <tr>
                    <td>
                        <div class="sel-utama">
                            <span class="inisial v{{ $loop->index % 5 }}">{{ mb_strtoupper(mb_substr($l->pesertaMagang->pengguna->nama_lengkap, 0, 1)) }}</span>
                            <div>
                                <b>{{ $l->pesertaMagang->pengguna->nama_lengkap }}</b>
                                <small>{{ $l->pesertaMagang->pengguna->email }}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="sel-ikon"><x-ikon nama="kalender" /> {{ $l->tanggal_kegiatan->translatedFormat('d M Y') }}</span></td>
                    <td>
                        @if ($l->waktu_kirim)
                            <span class="sel-ikon"><x-ikon nama="jam" /> {{ $l->waktu_kirim->translatedFormat('d M Y, H:i') }}</span>
                        @else
                            <span class="redup">—</span>
                        @endif
                    </td>
                    <td class="aksi-tabel">
                        <a class="btn btn-kecil" href="{{ route('mentor.logbook.show', $l) }}"><x-ikon nama="mata" /> Tinjau</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">
                    <div class="kosong">
                        <div class="ilustrasi"><x-ikon nama="papan-centang" /></div>
                        <b>Tidak ada antrean pemeriksaan.</b>
                        Semua logbook yang dikirim peserta bimbingan Anda sudah diperiksa.
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="kartu kartu-tabel">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat"><x-ikon nama="papan-centang" /></span>
            <div>
                <h2>Sudah diperiksa</h2>
                <p>Riwayat keputusan pemeriksaan logbook peserta bimbingan Anda.</p>
            </div>
        </div>
    </div>
    <div class="tabel-wadah">
        <table>
            <thead>
                <tr>
                    <th>Peserta</th>
                    <th>Tanggal Kegiatan</th>
                    <th>Status</th>
                    <th>Waktu Diperiksa</th>
                    <th class="aksi-tabel">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($terdahulu as $l)
                <tr>
                    <td>
                        <div class="sel-utama">
                            <span class="inisial v{{ $loop->index % 5 }}">{{ mb_strtoupper(mb_substr($l->pesertaMagang->pengguna->nama_lengkap, 0, 1)) }}</span>
                            <div>
                                <b>{{ $l->pesertaMagang->pengguna->nama_lengkap }}</b>
                                <small>{{ $l->pesertaMagang->pengguna->email }}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="sel-ikon"><x-ikon nama="kalender" /> {{ $l->tanggal_kegiatan->translatedFormat('d M Y') }}</span></td>
                    <td><span class="status {{ $statusKelas[$l->status] ?? 'status-netral' }}">{{ $l->status }}</span></td>
                    <td>
                        @if ($l->waktu_periksa)
                            <span class="sel-ikon"><x-ikon nama="jam" /> {{ $l->waktu_periksa->translatedFormat('d M Y, H:i') }}</span>
                        @else
                            <span class="redup">—</span>
                        @endif
                    </td>
                    <td class="aksi-tabel">
                        <a class="btn btn-garis btn-kecil" href="{{ route('mentor.logbook.show', $l) }}"><x-ikon nama="mata" /> Detail</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">
                    <div class="kosong">
                        <div class="ilustrasi"><x-ikon nama="buku" /></div>
                        <b>Belum ada logbook yang diperiksa.</b>
                        Riwayat logbook yang telah Anda setujui atau minta revisi akan muncul di sini.
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
