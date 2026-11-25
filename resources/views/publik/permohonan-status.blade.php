@use('App\Services\AlurPenerimaanService', 'Alur')
@extends('layouts.guest')

@section('judul', 'Status Permohonan '.$pengajuan->kode_pengajuan.' — DTI Universitas Andalas')
@section('kelas-body', 'guest-lp')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    <link rel="stylesheet" href="{{ asset('css/permohonan.css') }}">
    <link rel="stylesheet" href="{{ asset('css/penerimaan.css') }}">
@endpush

@section('nav-kanan')
    <a class="tautan" href="{{ route('publik.permohonan.create') }}">Jalur Institusi</a>
    <form method="POST" action="{{ route('publik.permohonan.keluar') }}">
        @csrf
        <button class="btn btn-garis">Keluar</button>
    </form>
@endsection

@section('konten')
@php
    $status = $pengajuan->status_pengajuan;
    // Status yang ditampilkan ke institusi: "Lengkap" masih proses internal, jadi disebut "Sedang Ditinjau".
    $labelStatus = match ($status) { 'Lengkap' => 'Sedang Ditinjau', default => $status };
    $sudahDiputuskan = $status === 'Selesai Diputuskan';
@endphp
<div class="publik">
    <section class="publik-bagian publik-bagian--terang inst-terkirim">
        <div class="publik-wadah inst-status">
            @if (session('gagal'))
                <div class="alert alert-gagal inst-alert"><x-ikon nama="peringatan" /><span>{{ session('gagal') }}</span></div>
            @endif

            <header class="inst-kartu inst-kepala">
                <p class="publik-label">Status permohonan</p>
                <div class="inst-status-judul">
                    <h1>{{ $pengajuan->nama_institusi }}</h1>
                    <span class="status {{ Alur::kelasStatus($status === 'Lengkap' ? 'Diajukan' : $status) }}">{{ $labelStatus }}</span>
                </div>
                <dl class="pn-dl">
                    <dt>Kode</dt><dd class="inst-mono">{{ $pengajuan->kode_pengajuan }}</dd>
                    <dt>Nomor surat</dt><dd>{{ $pengajuan->nomor_surat }} · {{ $pengajuan->tanggal_surat?->translatedFormat('d F Y') }}</dd>
                    <dt>Narahubung</dt><dd>{{ $kontak['nama'] ?: '—' }}</dd>
                    <dt>Diajukan</dt><dd>{{ $pengajuan->tanggal_pengajuan?->translatedFormat('d F Y, H:i') }}</dd>
                </dl>
            </header>

            @if ($status === 'Perlu Perbaikan')
                <section class="inst-kartu inst-perbaikan">
                    <h2 class="inst-judul-bagian"><span class="inst-no inst-no--merah">!</span> Permohonan perlu diperbaiki</h2>
                    <p class="inst-ket">DTI sudah memeriksa permohonan Anda dan meminta perbaikan berikut:</p>
                    <p class="pn-kutip merah">{{ $pengajuan->catatan_perbaikan }}</p>
                    @if ($tidakSesuai->isNotEmpty())
                        <p class="pn-subjudul">Berkas yang tidak sesuai</p>
                        <ul class="pn-dok">
                            @foreach ($tidakSesuai as $d)
                                <li class="pn-dok-baris">
                                    <div class="pn-dok-atas">
                                        <span class="pn-dok-ikon">{{ strtoupper(pathinfo($d->nama_berkas, PATHINFO_EXTENSION) ?: 'DOK') }}</span>
                                        <div class="pn-dok-nama"><b>{{ Alur::labelJenisDokumen($d->jenis_dokumen) }}</b><small>{{ $d->nama_berkas }}</small></div>
                                        <span class="status status-gagal">Tidak Sesuai</span>
                                    </div>
                                    @if ($d->catatan_pemeriksaan)<p class="pn-dok-catatan">{{ $d->catatan_pemeriksaan }}</p>@endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="inst-kirim-aksi">
                        <a class="btn" href="{{ route('publik.permohonan.perbaikan') }}"><x-ikon nama="ubah" /> Perbaiki Permohonan</a>
                        <span class="petunjuk">Setelah dikirim, permohonan akan diperiksa ulang oleh DTI.</span>
                    </div>
                </section>
            @elseif ($sudahDiputuskan)
                <section class="inst-kartu">
                    <h2 class="inst-judul-bagian"><span class="inst-no">✓</span> Hasil keputusan</h2>
                    <p class="inst-ket">Keputusan Direktur DTI untuk setiap peserta yang diajukan:</p>
                    <div class="tp-gulir">
                        <table class="tp-tabel inst-tabel-hasil">
                            <thead><tr><th>Nama peserta</th><th>NIM/NIS</th><th>Keputusan</th></tr></thead>
                            <tbody>
                            @foreach ($pengajuan->calonPeserta as $c)
                                @php $hasil = $c->keputusanPenerimaan?->keputusan; @endphp
                                <tr>
                                    <td data-label="Nama peserta"><b>{{ $c->nama_lengkap }}</b></td>
                                    <td data-label="NIM/NIS">{{ $c->nomor_induk_pendidikan ?? '—' }}</td>
                                    <td data-label="Keputusan"><span class="status {{ Alur::kelasStatus($hasil) }}">{{ $hasil ?? '—' }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="petunjuk" style="margin-top:12px">Peserta yang diterima akan dihubungi DTI untuk informasi penempatan.</p>
                </section>
            @else
                <section class="inst-kartu">
                    <div class="pn-hasil proses" style="margin-top:0">
                        <x-ikon nama="jam" />
                        <div><b>Permohonan sedang diproses</b><p>Silakan cek halaman ini secara berkala dengan kode permohonan dan email narahubung.</p></div>
                    </div>
                </section>
            @endif

            <section class="inst-kartu">
                <h2 class="inst-judul-bagian"><span class="inst-no">⋯</span> Alur permohonan</h2>
                @include('penerimaan._alur', ['langkah' => $langkah])
            </section>

            @unless ($sudahDiputuskan)
                <section class="inst-kartu">
                    <h2 class="inst-judul-bagian"><span class="inst-no">{{ $pengajuan->calonPeserta->count() }}</span> Peserta yang diajukan</h2>
                    <ul class="inst-daftar-peserta">
                        @foreach ($pengajuan->calonPeserta as $c)
                            <li><b>{{ $c->nama_lengkap }}</b><span>{{ $c->nomor_induk_pendidikan ?? '—' }} · {{ $c->jurusan_prodi ?? '—' }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endunless
        </div>
    </section>
</div>
@endsection
