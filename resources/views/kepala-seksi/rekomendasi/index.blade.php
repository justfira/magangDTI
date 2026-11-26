@use('App\Services\AlurPenerimaanService', 'Alur')
@extends('layouts.app')
@section('judul', 'Rekomendasi Penerimaan')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/penerimaan.css') }}">
@endpush
@section('konten')
@php
    $tautan = fn (array $ubah = []) => route('kasi.rekomendasi.index', array_filter(array_merge(
        ['tab' => $tab === 'riwayat' ? 'riwayat' : null, 'cari' => $cari ?: null, 'page' => request('page')], $ubah)));
@endphp

<div class="kepala-halaman">
    <div>
        <p class="kicker">Penerimaan · Kepala Seksi</p>
        <h1>Rekomendasi Penerimaan</h1>
        <p class="sub-judul">Beri rekomendasi Terima atau Tolak untuk calon dari pengajuan yang sudah dinyatakan lengkap oleh Superadmin.</p>
    </div>
</div>

<div class="statistik-grid">
    <div class="stat stat--kuning">
        <span class="ikon-bulat"><x-ikon nama="jam" /></span>
        <div class="nilai">{{ $ringkasan['menunggu'] }}</div>
        <div class="label">Lengkap · menunggu rekomendasi</div>
    </div>
    <div class="stat">
        <span class="ikon-bulat"><x-ikon nama="papan-centang" /></span>
        <div class="nilai">{{ $ringkasan['riwayat'] }}</div>
        <div class="label">Sudah direkomendasikan</div>
    </div>
</div>

<div class="pn-dua">
    <div class="kartu kartu-tabel">
        <nav class="tab-bar" aria-label="Filter" style="margin:16px 20px 0">
            <a class="tab {{ $tab === 'menunggu' ? 'aktif' : '' }}" href="{{ route('kasi.rekomendasi.index') }}">Menunggu ({{ $ringkasan['menunggu'] }})</a>
            <a class="tab {{ $tab === 'riwayat' ? 'aktif' : '' }}" href="{{ route('kasi.rekomendasi.index', ['tab' => 'riwayat']) }}">Riwayat ({{ $ringkasan['riwayat'] }})</a>
        </nav>
        <form class="pn-filter" method="GET" action="{{ route('kasi.rekomendasi.index') }}">
            @if ($tab === 'riwayat')<input type="hidden" name="tab" value="riwayat">@endif
            <span class="input-ikon"><x-ikon nama="cari" /><input name="cari" value="{{ $cari }}" placeholder="Cari nama, institusi, atau kode" aria-label="Cari"></span>
            <button class="btn"><x-ikon nama="filter" /> Filter</button>
        </form>
        <div class="tabel-wadah">
        <table>
            <thead><tr><th>Pendaftar</th><th>Jalur</th><th>{{ $tab === 'riwayat' ? 'Rekomendasi' : 'Status' }}</th><th class="aksi-tabel">Aksi</th></tr></thead>
            <tbody>
            @forelse ($daftar as $c)
                <tr @class(['pn-terpilih' => $terpilih?->id_calon === $c->id_calon])>
                    <td>
                        <b>{{ $c->nama_lengkap }}</b><br>
                        <small class="redup">{{ $c->pengajuan->kode_pengajuan }}{{ $c->institusi_asal ? ' · '.$c->institusi_asal : '' }}</small>
                    </td>
                    <td><span class="chip {{ $c->pengajuan->jalur === 'INSTITUSI' ? 'chip-hijau' : '' }}">{{ Alur::labelJalur($c->pengajuan->jalur) }}</span></td>
                    <td>
                        @if ($c->keputusanPenerimaan)
                            <span class="status {{ Alur::kelasStatus($c->keputusanPenerimaan->rekomendasi) }}">{{ $c->keputusanPenerimaan->rekomendasi }}</span>
                        @else
                            <span class="status status-info">Lengkap</span>
                        @endif
                    </td>
                    <td class="aksi-tabel"><a class="btn btn-garis btn-kecil" href="{{ $tautan(['calon' => $c->id_calon]) }}#panel"><x-ikon nama="mata" /> Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="4">
                    <div class="kosong">
                        <div class="ilustrasi"><x-ikon nama="kotak-masuk" /></div>
                        <b>{{ Alur::PESAN_KOSONG }}</b>
                        {{ $tab === 'menunggu' ? 'Belum ada pengajuan lengkap yang menunggu rekomendasi.' : 'Belum ada rekomendasi yang diberikan.' }}
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        {{ $daftar->links() }}
    </div>

    <div class="pn-kolom pn-lengket" id="panel">
        @if (! $terpilih)
            <section class="kartu">
                <div class="pn-panel-kosong">
                    <x-ikon nama="papan-centang" />
                    <b>Pilih calon dari daftar</b>
                    Klik <b>Detail</b> untuk melihat data, dokumen, dan memberi rekomendasi.
                </div>
            </section>
        @else
            @php $p = $terpilih->pengajuan; $k = $terpilih->keputusanPenerimaan; @endphp
            <section class="kartu">
                <div class="pn-kepala">
                    <div>
                        <h2>{{ $terpilih->nama_lengkap }}</h2>
                        <p>{{ Alur::labelJalur($p->jalur) }} · {{ $p->kode_pengajuan }}</p>
                    </div>
                    <span class="status {{ Alur::kelasStatus($p->status_pengajuan) }}">{{ $p->status_pengajuan }}</span>
                </div>
                @include('penerimaan._data-pengajuan', ['pengajuan' => $p, 'calon' => $terpilih])

                <p class="pn-subjudul">Riwayat pemeriksaan</p>
                <p class="kecil" style="margin:0">Dinyatakan <b>{{ $p->status_pengajuan === 'Perlu Perbaikan' ? 'Perlu Perbaikan' : 'Lengkap' }}</b> oleh {{ $p->pemeriksa?->pengguna?->nama_lengkap ?? '—' }} · {{ $p->waktu_pemeriksaan?->translatedFormat('d M Y, H:i') ?? '—' }}</p>

                <p class="pn-subjudul">Dokumen persyaratan</p>
                @include('penerimaan._dokumen', ['checklist' => $checklist])
            </section>

            <section class="kartu pn-form">
                <div class="kartu-kepala"><div class="kartu-judul"><span class="ikon-bulat olive"><x-ikon nama="papan-centang" /></span><h2>Rekomendasi</h2></div></div>
                @if ($k)
                    <div class="pn-terkunci">
                        <x-ikon nama="perisai" />
                        <div>
                            Direkomendasikan <span class="status {{ Alur::kelasStatus($k->rekomendasi) }}">{{ $k->rekomendasi }}</span>
                            oleh {{ $k->kepalaSeksi?->pengguna?->nama_lengkap ?? '—' }} · {{ $k->waktu_rekomendasi?->translatedFormat('d M Y, H:i') }}
                            @if ($k->catatan_rekomendasi)<p class="pn-kutip">{{ $k->catatan_rekomendasi }}</p>@endif
                            <p style="margin:8px 0 0">Keputusan Direktur:
                                @if ($k->keputusan)<span class="status {{ Alur::kelasStatus($k->keputusan) }}">{{ $k->keputusan }}</span>@else<b>menunggu</b>@endif
                            </p>
                        </div>
                    </div>
                @elseif ($p->status_pengajuan !== 'Lengkap')
                    <div class="alert alert-peringatan"><x-ikon nama="peringatan" /><span>{{ Alur::PESAN_BELUM_LENGKAP }}</span></div>
                @else
                    <form method="POST" action="{{ route('kasi.rekomendasi.store', $terpilih) }}" class="form">
                        @csrf
                        <label>Catatan <span class="wajib">*</span>
                            <textarea name="catatan_rekomendasi" maxlength="1000" placeholder="Alasan rekomendasi, mis. kompetensi sesuai kebutuhan seksi." @class(['salah' => $errors->has('catatan_rekomendasi')])>{{ old('catatan_rekomendasi') }}</textarea>
                            @error('catatan_rekomendasi')<span class="pesan-salah">{{ $message }}</span>@enderror
                        </label>
                        @error('rekomendasi')<span class="pesan-salah">{{ $message }}</span>@enderror
                        <p class="petunjuk">Kolom wajib diisi kecuali yang ditandai opsional. Rekomendasi diteruskan ke Direktur.</p>
                        <div class="pn-tombol">
                            <button class="btn" name="rekomendasi" value="Terima"><x-ikon nama="centang-saja" /> Terima</button>
                            <button class="btn btn-garis-merah" name="rekomendasi" value="Tolak"><x-ikon nama="silang" /> Tolak</button>
                        </div>
                    </form>
                @endif
            </section>
        @endif
    </div>
</div>
@endsection
