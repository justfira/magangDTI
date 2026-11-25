@use('App\Services\AlurPenerimaanService', 'Alur')
@extends('layouts.app')
@section('judul', 'Status Pendaftaran')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/penerimaan.css') }}">
@endpush
@section('konten')
<div class="kepala-halaman">
    <div>
        <p class="kicker">Pendaftaran</p>
        <h1>Status Pendaftaran</h1>
        <p class="sub-judul">Pantau pemeriksaan berkas dan hasil penerimaan magang Anda.</p>
    </div>
</div>

@forelse ($pengajuan as $p)
    @php
        $k = $p->calonPeserta->first()?->keputusanPenerimaan;
        $tidakSesuai = $p->dokumenPengajuan->where('hasil_pemeriksaan', 'Tidak Sesuai');
    @endphp
    <div class="pn-dua pn-dua--rinci" style="margin-bottom:18px">
        <section class="kartu">
            <div class="pn-kepala">
                <div>
                    <h2>{{ $p->program?->nama_program ?? 'Pengajuan magang' }}</h2>
                    <p>{{ $p->kode_pengajuan }} · Diajukan {{ $p->tanggal_pengajuan?->translatedFormat('d F Y') }}</p>
                </div>
                <span class="status {{ Alur::kelasStatus($p->status_pengajuan) }}">{{ $p->status_pengajuan }}</span>
            </div>

            @if ($k?->keputusan === 'Diterima')
                <div class="pn-hasil diterima"><x-ikon nama="medali" /><div><b>Selamat, Anda diterima!</b><p>Informasi penempatan seksi dan mentor akan muncul setelah ditetapkan Kepala Seksi.</p></div></div>
            @elseif ($k?->keputusan === 'Ditolak')
                <div class="pn-hasil ditolak"><x-ikon nama="info" /><div><b>Mohon maaf, pengajuan Anda belum dapat diterima.</b>@if ($k->catatan_keputusan)<p>{{ $k->catatan_keputusan }}</p>@endif</div></div>
            @elseif ($p->status_pengajuan === 'Perlu Perbaikan')
                <div class="alert alert-gagal" style="margin-top:16px">
                    <x-ikon nama="peringatan" />
                    <div>
                        <b>Pengajuan perlu perbaikan</b>
                        <p class="pn-kutip merah">{{ $p->catatan_perbaikan }}</p>
                        @if ($tidakSesuai->isNotEmpty())
                            <ul style="margin-top:8px">
                                @foreach ($tidakSesuai as $d)
                                    <li><b>{{ $d->syarat?->nama_syarat ?? Alur::labelJenisDokumen($d->jenis_dokumen) }}</b>{{ $d->catatan_pemeriksaan ? ': '.$d->catatan_pemeriksaan : '' }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <a class="btn btn-kecil" style="margin-top:10px" href="{{ route('peserta.pendaftaran.edit', $p) }}"><x-ikon nama="ubah" /> Perbaiki Pengajuan</a>
                    </div>
                </div>
            @else
                <div class="pn-hasil proses"><x-ikon nama="jam" /><div><b>Pengajuan sedang diproses</b><p>Kami akan memberi tahu Anda lewat notifikasi setiap ada perubahan status.</p></div></div>
            @endif
        </section>

        <section class="kartu">
            <div class="kartu-kepala"><div class="kartu-judul"><span class="ikon-bulat kuning"><x-ikon nama="jam" /></span><h2>Alur Persetujuan</h2></div></div>
            @include('penerimaan._alur', ['langkah' => Alur::langkahAlur($p)])
        </section>
    </div>
@empty
    <div class="kartu">
        <div class="kosong">
            <div class="ilustrasi"><x-ikon nama="kotak-masuk" /></div>
            <b>{{ Alur::PESAN_KOSONG }}</b>
            Anda belum memiliki pengajuan magang.
            <br><a class="btn" href="{{ route('informasi-program') }}">Lihat Program Magang</a>
        </div>
    </div>
@endforelse
@endsection
