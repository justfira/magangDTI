@use('App\Services\AlurPenerimaanService', 'Alur')
@extends('layouts.app')
@section('judul', 'Detail Pengajuan')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/penerimaan.css') }}">
@endpush
@section('konten')
@php
    $ukuran = fn ($b) => $b ? ($b >= 1048576 ? number_format($b / 1048576, 1, ',', '.').' MB' : number_format(max(1, ceil($b / 1024)), 0, ',', '.').' KB') : null;
    $adaKeputusan = $pengajuan->calonPeserta->contains(fn ($c) => $c->keputusanPenerimaan);
@endphp

<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('superadmin.verifikasi.index') }}"><x-ikon nama="kembali" /> Kembali ke Verifikasi Pengajuan</a>
        <h1>{{ Alur::namaPengajuan($pengajuan) }}</h1>
        <p class="sub-judul">
            {{ Alur::labelJalur($pengajuan->jalur) }} · Diajukan {{ $pengajuan->tanggal_pengajuan?->translatedFormat('d F Y') }}
            &nbsp;<span class="status {{ Alur::kelasStatus($pengajuan->status_pengajuan) }}">{{ $pengajuan->status_pengajuan }}</span>
        </p>
    </div>
    @if ($pengajuan->jalur === 'INSTITUSI')
        <div class="kepala-aksi">
            <a class="btn btn-garis" href="{{ route('superadmin.permohonan.show', $pengajuan) }}"><x-ikon nama="gedung" /> Lihat Permohonan</a>
        </div>
    @endif
</div>

<div class="pn-dua pn-dua--rinci">
    <div class="pn-kolom">
        <section class="kartu">
            <div class="kartu-kepala"><div class="kartu-judul"><span class="ikon-bulat"><x-ikon nama="{{ $pengajuan->jalur === 'INSTITUSI' ? 'gedung' : 'pengguna' }}" /></span><h2>{{ $pengajuan->jalur === 'INSTITUSI' ? 'Data Institusi' : 'Data Pendaftar' }}</h2></div></div>
            @include('penerimaan._data-pengajuan', ['pengajuan' => $pengajuan])
        </section>

        @if ($pengajuan->jalur === 'INSTITUSI' || $adaKeputusan)
            <section class="kartu kartu-tabel">
                <div class="kartu-kepala">
                    <div>
                        <h2>{{ $pengajuan->jalur === 'INSTITUSI' ? 'Peserta yang diajukan' : 'Hasil penerimaan' }}</h2>
                        <p>Rekomendasi Kepala Seksi dan keputusan Direktur per calon.</p>
                    </div>
                </div>
                <div class="tabel-wadah">
                <table>
                    <thead><tr><th>Nama</th><th>NIM/NIS</th><th>Rekomendasi</th><th>Keputusan</th></tr></thead>
                    <tbody>
                    @foreach ($pengajuan->calonPeserta as $c)
                        @php $k = $c->keputusanPenerimaan; @endphp
                        <tr>
                            <td><b>{{ $c->nama_lengkap }}</b><br><small class="redup">{{ $c->jurusan_prodi ?? '—' }}</small></td>
                            <td>{{ $c->nomor_induk_pendidikan ?? '—' }}</td>
                            <td>@if ($k)<span class="status {{ Alur::kelasStatus($k->rekomendasi) }}">{{ $k->rekomendasi }}</span>@else<span class="redup">Menunggu</span>@endif</td>
                            <td>@if ($k?->keputusan)<span class="status {{ Alur::kelasStatus($k->keputusan) }}">{{ $k->keputusan }}</span>@else<span class="redup">Menunggu</span>@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            </section>
        @endif

        <section class="kartu">
            <div class="kartu-kepala"><div class="kartu-judul"><span class="ikon-bulat peru"><x-ikon nama="berkas" /></span><h2>Dokumen Persyaratan</h2></div></div>

            @if ($bisaDiperiksa)
                <p class="redup kecil" style="margin-top:0">Buka setiap berkas, lalu tandai <b>Sesuai</b> atau <b>Tidak Sesuai</b>. Berkas Tidak Sesuai wajib diberi catatan.</p>
                <ul class="pn-dok">
                    @foreach ($checklist as $baris)
                        @php $d = $baris['dokumen']; $id = $d?->id_dokumen; $lama = $id ? old("dokumen.$id.hasil", $d->hasil_pemeriksaan) : null; @endphp
                        <li class="pn-dok-baris">
                            <div class="pn-dok-atas">
                                <span class="pn-dok-ikon {{ $d ? '' : 'kosong' }}">{{ $d ? strtoupper(pathinfo($d->nama_berkas, PATHINFO_EXTENSION) ?: 'DOK') : '—' }}</span>
                                <div class="pn-dok-nama">
                                    <b>{{ $baris['nama'] }}@if ($baris['wajib']) <span class="wajib">*</span>@endif</b>
                                    <small>{{ $d ? $d->nama_berkas.($ukuran($d->ukuran_bytes) ? ' · '.$ukuran($d->ukuran_bytes) : '') : 'Belum diunggah oleh pendaftar' }}</small>
                                </div>
                                @if ($d)
                                    <a class="btn btn-garis btn-kecil" href="{{ route('pengajuan.dokumen', $d) }}" target="_blank" rel="noopener"><x-ikon nama="mata" /> Lihat</a>
                                @else
                                    <span class="status status-gagal">Belum ada</span>
                                @endif
                            </div>
                            @if ($d)
                                <div class="pn-dok-hasil">
                                    <label><input type="radio" form="form-periksa" name="dokumen[{{ $id }}][hasil]" value="Sesuai" @checked($lama === 'Sesuai')> Sesuai</label>
                                    <label><input type="radio" form="form-periksa" name="dokumen[{{ $id }}][hasil]" value="Tidak Sesuai" @checked($lama === 'Tidak Sesuai')> Tidak Sesuai</label>
                                    <input type="text" form="form-periksa" name="dokumen[{{ $id }}][catatan]" maxlength="500"
                                           value="{{ old("dokumen.$id.catatan", $d->catatan_pemeriksaan) }}" placeholder="Catatan dokumen (wajib jika Tidak Sesuai)"
                                           aria-label="Catatan {{ $baris['nama'] }}" @class(['salah' => $errors->has("dokumen.$id.catatan")])>
                                    @error("dokumen.$id.hasil")<span class="pesan-salah">{{ $message }}</span>@enderror
                                    @error("dokumen.$id.catatan")<span class="pesan-salah">{{ $message }}</span>@enderror
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                @include('penerimaan._dokumen', ['checklist' => $checklist])
            @endif
        </section>
    </div>

    <div class="pn-kolom pn-lengket">
        <section class="kartu">
            <div class="kartu-kepala"><div class="kartu-judul"><span class="ikon-bulat kuning"><x-ikon nama="jam" /></span><h2>Alur Persetujuan</h2></div></div>
            @include('penerimaan._alur', ['langkah' => $langkah])
        </section>

        <section class="kartu pn-form">
            <div class="kartu-kepala"><div class="kartu-judul"><span class="ikon-bulat olive"><x-ikon nama="papan-centang" /></span><h2>Pemeriksaan</h2></div></div>

            @if ($bisaDiperiksa)
                <form id="form-periksa" method="POST" action="{{ route('superadmin.verifikasi.periksa', $pengajuan) }}" class="form">
                    @csrf
                    @if ($pengajuan->status_pengajuan === 'Perlu Perbaikan')
                        <div class="pn-terkunci" style="margin-bottom:14px"><x-ikon nama="info" /><span>Pengajuan ini sebelumnya ditandai Perlu Perbaikan. Periksa ulang bila perbaikan sudah diterima.</span></div>
                    @endif
                    @error('status')<div class="alert alert-gagal" style="margin-bottom:12px"><x-ikon nama="peringatan" /><span>{{ $message }}</span></div>@enderror
                    <label>Catatan perbaikan <small class="redup">(wajib jika Perlu Perbaikan)</small>
                        <textarea name="catatan_perbaikan" maxlength="2000" placeholder="mis. KTM tidak terbaca, mohon unggah ulang dengan resolusi lebih jelas." @class(['salah' => $errors->has('catatan_perbaikan')])>{{ old('catatan_perbaikan', $pengajuan->catatan_perbaikan) }}</textarea>
                        @error('catatan_perbaikan')<span class="pesan-salah">{{ $message }}</span>@enderror
                    </label>
                    <p class="petunjuk">{{ $pengajuan->jalur === 'DTI' ? 'Catatan perbaikan akan terlihat oleh pendaftar.' : 'Catatan perbaikan akan terlihat oleh institusi di halaman Cek Status Permohonan (kode + email narahubung).' }}</p>
                    <div class="pn-tombol">
                        <button class="btn" name="status" value="Lengkap"><x-ikon nama="centang-saja" /> Lengkap</button>
                        <button class="btn btn-garis-merah" name="status" value="Perlu Perbaikan"><x-ikon nama="peringatan" /> Perlu Perbaikan</button>
                    </div>
                </form>
            @else
                <div class="pn-terkunci">
                    <x-ikon nama="perisai" />
                    <span>
                        Diperiksa {{ $pengajuan->pemeriksa?->pengguna?->nama_lengkap ?? '—' }}
                        pada {{ $pengajuan->waktu_pemeriksaan?->translatedFormat('d F Y, H:i') ?? '—' }}.
                        Status <b>{{ $pengajuan->status_pengajuan }}</b> — pengajuan sudah diteruskan ke Kepala Seksi.
                    </span>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
