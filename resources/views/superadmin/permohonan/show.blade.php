@extends('layouts.app')
@section('judul', 'Detail Permohonan')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/permohonan.css') }}">
@endpush
@section('konten')
@php
    $kelasStatus = match ($pengajuan->status_pengajuan) {
        'Diajukan' => 'status-peringatan',
        'Perlu Perbaikan' => 'status-gagal',
        'Lengkap' => 'status-info',
        'Selesai Diputuskan' => 'status-sukses',
        default => 'status-netral',
    };
    $surat = $pengajuan->dokumenPengajuan->firstWhere('jenis_dokumen', \App\Services\PermohonanInstitusiService::JENIS_DOKUMEN);
    $ukuran = $surat?->ukuran_bytes
        ? ($surat->ukuran_bytes >= 1048576 ? number_format($surat->ukuran_bytes / 1048576, 1, ',', '.').' MB' : number_format(max(1, ceil($surat->ukuran_bytes / 1024)), 0, ',', '.').' KB')
        : null;
@endphp

<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('superadmin.permohonan.index') }}"><x-ikon nama="kembali" /> Kembali ke daftar permohonan</a>
        <h1>{{ $pengajuan->nama_institusi }}</h1>
        <p class="sub-judul">
            {{ $pengajuan->kode_pengajuan }} · Diajukan {{ $pengajuan->tanggal_pengajuan?->translatedFormat('d F Y, H:i') }}
            &nbsp;<span class="status {{ $kelasStatus }}">{{ $pengajuan->status_pengajuan }}</span>
        </p>
    </div>
    @if (\Illuminate\Support\Facades\Route::has('superadmin.verifikasi.show'))
        <div class="kepala-aksi">
            <a class="btn" href="{{ route('superadmin.verifikasi.show', $pengajuan) }}"><x-ikon nama="papan-centang" /> Periksa Kelengkapan</a>
        </div>
    @endif
</div>

<div class="rinci-grid">
    <section class="kartu">
        <div class="kartu-kepala"><div class="kartu-judul"><span class="ikon-bulat"><x-ikon nama="gedung" /></span><h2>Data institusi</h2></div></div>
        <dl class="dl-rinci">
            <dt>Nama institusi</dt><dd>{{ $pengajuan->nama_institusi }}</dd>
            <dt>Nomor surat</dt><dd>{{ $pengajuan->nomor_surat }}</dd>
            <dt>Tanggal surat</dt><dd>{{ $pengajuan->tanggal_surat?->translatedFormat('d F Y') ?? '—' }}</dd>
            <dt>Kontak</dt><dd>{{ $pengajuan->kontak_institusi ?: '—' }}</dd>
        </dl>
    </section>

    <section class="kartu">
        <div class="kartu-kepala"><div class="kartu-judul"><span class="ikon-bulat kuning"><x-ikon nama="jam" /></span><h2>Riwayat</h2></div></div>
        <dl class="dl-rinci">
            <dt>Sumber</dt>
            <dd>
                @if ($pengajuan->dicatat_oleh)
                    Dicatat Superadmin — {{ $pengajuan->dicatatOleh?->pengguna?->nama_lengkap ?? '—' }}
                @else
                    Diisi institusi lewat form online
                @endif
            </dd>
            <dt>Diajukan</dt><dd>{{ $pengajuan->tanggal_pengajuan?->translatedFormat('d F Y, H:i') ?? '—' }}</dd>
            <dt>Pemeriksa</dt><dd>{{ $pengajuan->pemeriksa?->pengguna?->nama_lengkap ?? 'Belum diperiksa' }}</dd>
            @if ($pengajuan->catatan_perbaikan)
                <dt>Catatan perbaikan</dt><dd>{{ $pengajuan->catatan_perbaikan }}</dd>
            @endif
        </dl>
        <div class="berkas-baris">
            <span class="berkas-ikon"><x-ikon nama="berkas" /></span>
            @if ($surat)
                <div class="berkas-nama">
                    <b>{{ $surat->nama_berkas }}</b>
                    <small class="redup">Surat pengajuan{{ $ukuran ? ' · '.$ukuran : '' }}</small>
                </div>
                <a class="btn btn-garis btn-kecil" href="{{ route('superadmin.permohonan.surat', $pengajuan) }}" target="_blank" rel="noopener"><x-ikon nama="mata" /> Lihat</a>
            @else
                <div class="berkas-nama"><b>Surat belum diunggah</b></div>
            @endif
        </div>
    </section>
</div>

<div class="kartu kartu-tabel">
    <div class="kartu-kepala">
        <div>
            <h2>Data peserta</h2>
            <p>{{ $pengajuan->calonPeserta->count() }} peserta diajukan dalam permohonan ini.</p>
        </div>
    </div>
    <div class="tabel-wadah">
    <table>
        <thead><tr><th>#</th><th>Nama lengkap</th><th>NIM/NIS</th><th>Jurusan/Prodi</th><th>Email</th><th>No. HP</th></tr></thead>
        <tbody>
        @foreach ($pengajuan->calonPeserta as $c)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td><b>{{ $c->nama_lengkap }}</b></td>
                <td>{{ $c->nomor_induk_pendidikan ?? '—' }}</td>
                <td>{{ $c->jurusan_prodi ?? '—' }}</td>
                <td>{{ $c->email ?? '—' }}</td>
                <td>{{ $c->no_hp ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endsection
