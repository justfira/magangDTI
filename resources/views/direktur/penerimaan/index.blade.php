@use('App\Services\AlurPenerimaanService', 'Alur')
@extends('layouts.app')
@section('judul', 'Persetujuan Penerimaan')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/penerimaan.css') }}">
@endpush
@section('konten')
@php
    $tautan = fn (array $ubah = []) => route('direktur.penerimaan.index', array_filter(array_merge(
        ['tab' => $tab === 'riwayat' ? 'riwayat' : null, 'cari' => $cari ?: null, 'page' => request('page')], $ubah)));
@endphp

<div class="kepala-halaman">
    <div>
        <p class="kicker">Penerimaan · Direktur</p>
        <h1>Persetujuan Penerimaan</h1>
        <p class="sub-judul">Setujui atau tolak penerimaan calon berdasarkan rekomendasi Kepala Seksi.</p>
    </div>
</div>

<div class="alert alert-peringatan pn-banner" role="note">
    <x-ikon nama="info" />
    <span><b>Menunggu konfirmasi DTI.</b> {{ Alur::BANNER_DIREKTUR }}</span>
</div>

<div class="statistik-grid">
    <div class="stat stat--kuning">
        <span class="ikon-bulat"><x-ikon nama="jam" /></span>
        <div class="nilai">{{ $ringkasan['menunggu'] }}</div>
        <div class="label">Menunggu keputusan</div>
    </div>
    <div class="stat">
        <span class="ikon-bulat"><x-ikon nama="medali" /></span>
        <div class="nilai">{{ $ringkasan['riwayat'] }}</div>
        <div class="label">Sudah diputuskan</div>
    </div>
</div>

<div class="pn-dua">
    <div class="kartu kartu-tabel">
        <nav class="tab-bar" aria-label="Filter" style="margin:16px 20px 0">
            <a class="tab {{ $tab === 'menunggu' ? 'aktif' : '' }}" href="{{ route('direktur.penerimaan.index') }}">Menunggu ({{ $ringkasan['menunggu'] }})</a>
            <a class="tab {{ $tab === 'riwayat' ? 'aktif' : '' }}" href="{{ route('direktur.penerimaan.index', ['tab' => 'riwayat']) }}">Riwayat ({{ $ringkasan['riwayat'] }})</a>
        </nav>
        <form class="pn-filter" method="GET" action="{{ route('direktur.penerimaan.index') }}">
            @if ($tab === 'riwayat')<input type="hidden" name="tab" value="riwayat">@endif
            <span class="input-ikon"><x-ikon nama="cari" /><input name="cari" value="{{ $cari }}" placeholder="Cari nama atau institusi" aria-label="Cari"></span>
            <button class="btn"><x-ikon nama="filter" /> Filter</button>
        </form>
        <div class="tabel-wadah">
        <table>
            <thead><tr><th>Pendaftar</th><th>Rekomendasi</th><th>Status</th><th class="aksi-tabel">Aksi</th></tr></thead>
            <tbody>
            @forelse ($daftar as $k)
                <tr @class(['pn-terpilih' => $terpilih?->id_keputusan === $k->id_keputusan])>
                    <td>
                        <b>{{ $k->calon->nama_lengkap }}</b><br>
                        <small class="redup">{{ Alur::labelJalur($k->calon->pengajuan->jalur) }} · {{ $k->calon->institusi_asal ?? $k->calon->pengajuan->kode_pengajuan }}</small>
                    </td>
                    <td><span class="status {{ Alur::kelasStatus($k->rekomendasi) }}">{{ $k->rekomendasi }}</span></td>
                    <td>
                        @if ($k->keputusan)
                            <span class="status {{ Alur::kelasStatus($k->keputusan) }}">{{ $k->keputusan }}</span>
                        @else
                            <span class="status status-peringatan">Menunggu</span>
                        @endif
                    </td>
                    <td class="aksi-tabel"><a class="btn btn-garis btn-kecil" href="{{ $tautan(['keputusan' => $k->id_keputusan]) }}#panel"><x-ikon nama="mata" /> Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="4">
                    <div class="kosong">
                        <div class="ilustrasi"><x-ikon nama="kotak-masuk" /></div>
                        <b>{{ Alur::PESAN_KOSONG }}</b>
                        {{ $tab === 'menunggu' ? 'Belum ada rekomendasi yang menunggu keputusan.' : 'Belum ada keputusan yang dibuat.' }}
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
                    <x-ikon nama="medali" />
                    <b>Pilih rekomendasi dari daftar</b>
                    Klik <b>Detail</b> untuk meninjau riwayat pemeriksaan dan rekomendasi.
                </div>
            </section>
        @else
            @php $c = $terpilih->calon; $p = $c->pengajuan; @endphp
            <section class="kartu">
                <div class="pn-kepala">
                    <div>
                        <h2>{{ $c->nama_lengkap }}</h2>
                        <p>{{ Alur::labelJalur($p->jalur) }} · {{ $p->kode_pengajuan }}</p>
                    </div>
                    <span class="status {{ Alur::kelasStatus($terpilih->keputusan ?? 'Menunggu') }}">{{ $terpilih->keputusan ?? 'Menunggu' }}</span>
                </div>
                @include('penerimaan._data-pengajuan', ['pengajuan' => $p, 'calon' => $c])

                <p class="pn-subjudul">Riwayat</p>
                <ol class="pn-alur">
                    <li class="selesai"><b>Pemeriksaan Superadmin · Lengkap</b><span>{{ $p->pemeriksa?->pengguna?->nama_lengkap ?? '—' }} · {{ $p->waktu_pemeriksaan?->translatedFormat('d M Y, H:i') ?? '—' }}</span></li>
                    <li class="selesai">
                        <b>Rekomendasi Kepala Seksi · {{ $terpilih->rekomendasi }}</b>
                        <span>{{ $terpilih->kepalaSeksi?->pengguna?->nama_lengkap ?? '—' }} · {{ $terpilih->waktu_rekomendasi?->translatedFormat('d M Y, H:i') }}</span>
                        @if ($terpilih->catatan_rekomendasi)<p class="pn-kutip">{{ $terpilih->catatan_rekomendasi }}</p>@endif
                    </li>
                    <li class="{{ $terpilih->keputusan ? 'selesai' : 'aktif' }}">
                        <b>Keputusan Direktur{{ $terpilih->keputusan ? ' · '.$terpilih->keputusan : '' }}</b>
                        <span>{{ $terpilih->keputusan ? ($terpilih->direktur?->pengguna?->nama_lengkap ?? '—').' · '.$terpilih->waktu_keputusan?->translatedFormat('d M Y, H:i') : 'Menunggu keputusan Anda' }}</span>
                        @if ($terpilih->catatan_keputusan)<p class="pn-kutip">{{ $terpilih->catatan_keputusan }}</p>@endif
                    </li>
                </ol>

                <p class="pn-subjudul">Dokumen persyaratan</p>
                @include('penerimaan._dokumen', ['checklist' => $checklist])
            </section>

            <section class="kartu pn-form">
                <div class="kartu-kepala"><div class="kartu-judul"><span class="ikon-bulat olive"><x-ikon nama="medali" /></span><h2>Keputusan</h2></div></div>
                @if ($terpilih->keputusan)
                    <div class="pn-terkunci"><x-ikon nama="perisai" /><span>Sudah diputuskan <b>{{ $terpilih->keputusan }}</b> pada {{ $terpilih->waktu_keputusan?->translatedFormat('d F Y, H:i') }}.</span></div>
                @else
                    <form method="POST" action="{{ route('direktur.penerimaan.update', $terpilih) }}" class="form">
                        @csrf
                        @method('PUT')
                        <label>Catatan <small class="redup">(opsional)</small>
                            <textarea name="catatan_keputusan" maxlength="1000" placeholder="Catatan untuk keputusan ini (opsional)." @class(['salah' => $errors->has('catatan_keputusan')])>{{ old('catatan_keputusan') }}</textarea>
                            @error('catatan_keputusan')<span class="pesan-salah">{{ $message }}</span>@enderror
                        </label>
                        @error('keputusan')<span class="pesan-salah">{{ $message }}</span>@enderror
                        <p class="petunjuk">Rekomendasi Kepala Seksi: <b>{{ $terpilih->rekomendasi }}</b>. Keputusan Anda menjadi hasil akhir dan dikirim ke {{ $p->jalur === 'DTI' ? 'akun pendaftar' : 'Superadmin' }}.</p>
                        <div class="pn-tombol">
                            <button class="btn" name="keputusan" value="Diterima"><x-ikon nama="centang-saja" /> Setujui (Diterima)</button>
                            <button class="btn btn-garis-merah" name="keputusan" value="Ditolak"><x-ikon nama="silang" /> Tolak</button>
                        </div>
                    </form>
                @endif
            </section>
        @endif
    </div>
</div>
@endsection
