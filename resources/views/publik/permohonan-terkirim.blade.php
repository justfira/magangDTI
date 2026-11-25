@extends('layouts.guest')

@section('judul', 'Permohonan Terkirim — DTI Universitas Andalas')
@section('kelas-body', 'guest-lp')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    <link rel="stylesheet" href="{{ asset('css/permohonan.css') }}">
@endpush

@section('nav-kanan')
    <a class="tautan" href="{{ route('informasi-program') }}">Program Magang</a>
    <a class="btn btn-garis" href="{{ route('publik.permohonan.create') }}">Form Institusi</a>
@endsection

@section('konten')
<div class="publik">
    <section class="publik-bagian publik-bagian--terang inst-terkirim">
        <div class="publik-wadah">
            <div class="inst-kartu inst-sukses">
                <span class="inst-sukses-ikon"><x-ikon nama="centang-saja" /></span>
                <p class="publik-label">Permohonan terkirim</p>
                <h1>Terima kasih, permohonan Anda sudah kami terima.</h1>
                <p>Simpan kode berikut — kode ini dipakai untuk mengecek status permohonan dan mengirim perbaikan bila diminta.</p>

                <div class="inst-kode" aria-label="Kode permohonan">
                    <span class="inst-kode-label">Kode permohonan</span>
                    <span class="inst-kode-nilai">{{ $ringkas['kode'] }}</span>
                </div>

                <dl class="dl-rinci inst-ringkas">
                    <dt>Institusi</dt><dd>{{ $ringkas['institusi'] }}</dd>
                    <dt>Nomor surat</dt><dd>{{ $ringkas['nomor_surat'] }}</dd>
                    <dt>Jumlah peserta</dt><dd>{{ $ringkas['jumlah_peserta'] }} orang</dd>
                    <dt>Status</dt><dd><span class="status status-peringatan">Diajukan</span></dd>
                </dl>

                <div class="info-kotak">
                    <x-ikon nama="info" />
                    <p>Pantau status, catatan perbaikan, dan hasil keputusan di halaman <b>Cek Status</b> dengan kode di atas dan email narahubung <b>{{ $ringkas['email'] }}</b>.</p>
                </div>

                <div class="inst-kirim-aksi">
                    <a class="btn" href="{{ route('publik.permohonan.cek', ['kode' => $ringkas['kode']]) }}">Cek Status Permohonan</a>
                    <a class="btn btn-garis" href="{{ route('informasi-program') }}">Kembali ke Beranda</a>
                    <a class="btn btn-halus" href="{{ route('publik.permohonan.create') }}">Kirim Permohonan Lain</a>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
