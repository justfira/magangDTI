@extends('layouts.app')
@section('judul', 'Tinjau Logbook')
@section('konten')
@php
    $statusKelas = ['Dikirim' => 'status-peringatan', 'Disetujui' => 'status-sukses', 'Perlu Perbaikan' => 'status-gagal'];
    $peserta = $logbook->pesertaMagang;
@endphp

<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('mentor.logbook.index') }}"><x-ikon nama="kembali" /> Kembali ke daftar logbook</a>
        <h1>Tinjau Logbook</h1>
        <p class="sub-judul">Periksa kesesuaian uraian aktivitas harian peserta bimbingan sebelum menetapkan keputusan.</p>
    </div>
    <div class="kepala-aksi">
        <span class="status {{ $statusKelas[$logbook->status] ?? 'status-netral' }}">{{ $logbook->status }}</span>
    </div>
</div>

<div class="kartu">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat"><x-ikon nama="pengguna" /></span>
            <div>
                <h2>Informasi logbook</h2>
                <p>Detail waktu pengiriman dan pemeriksaan catatan harian peserta.</p>
            </div>
        </div>
    </div>
    <table>
        <tr><th style="width:200px">Peserta</th><td><b>{{ $peserta->pengguna->nama_lengkap }}</b></td></tr>
        <tr>
            <th>Tanggal kegiatan</th>
            <td><span class="sel-ikon"><x-ikon nama="kalender" /> {{ $logbook->tanggal_kegiatan->translatedFormat('l, d M Y') }}</span></td>
        </tr>
        <tr>
            <th>Waktu kirim</th>
            <td>
                @if ($logbook->waktu_kirim)
                    <span class="sel-ikon"><x-ikon nama="jam" /> {{ $logbook->waktu_kirim->translatedFormat('d M Y, H:i') }}</span>
                @else
                    <span class="redup">—</span>
                @endif
            </td>
        </tr>
        @if ($logbook->waktu_periksa)
            <tr>
                <th>Waktu diperiksa</th>
                <td><span class="sel-ikon"><x-ikon nama="jam" /> {{ $logbook->waktu_periksa->translatedFormat('d M Y, H:i') }}</span></td>
            </tr>
            <tr><th>Catatan pemeriksaan</th><td>{{ $logbook->catatan_mentor ?? '—' }}</td></tr>
        @endif
    </table>
</div>

<div class="kartu">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat olive"><x-ikon nama="buku" /></span>
            <div>
                <h2>Uraian aktivitas</h2>
                <p>Catatan pekerjaan yang dilaporkan peserta pada tanggal kegiatan tersebut.</p>
            </div>
        </div>
    </div>
    <p style="white-space:pre-line;margin:0">{{ $logbook->uraian_aktivitas }}</p>
</div>

@if ($logbook->status === 'Dikirim')
<form class="kartu form form-kartu" method="POST" action="{{ route('mentor.logbook.periksa', $logbook) }}">
    @csrf
    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat kuning"><x-ikon nama="papan-centang" /></span> Keputusan pemeriksaan</h2>
            <p>Setujui logbook apabila aktivitas sudah sesuai, atau kembalikan untuk perbaikan peserta.</p>
        </div>
        <div class="form-bagian-isi">
            <div class="info-kotak">
                <x-ikon nama="info" />
                <p>Catatan pemeriksaan <b>wajib diisi</b> apabila Anda memilih status <b>Perlu Perbaikan</b>.</p>
            </div>

            <label>Keputusan
                <select name="keputusan" required>
                    <option value="Disetujui" @selected(old('keputusan') === 'Disetujui')>Disetujui</option>
                    <option value="Perlu Perbaikan" @selected(old('keputusan') === 'Perlu Perbaikan')>Perlu Perbaikan</option>
                </select>
            </label>
            <label>Catatan untuk peserta <small class="redup">(wajib apabila Perlu Perbaikan)</small>
                <textarea name="catatan_mentor" rows="4" placeholder="Umpan balik untuk peserta...">{{ old('catatan_mentor') }}</textarea>
            </label>
        </div>
    </div>
    <div class="form-kaki">
        <a class="btn btn-halus" href="{{ route('mentor.logbook.index') }}">Batal</a>
        <button class="btn"><x-ikon nama="centang-saja" /> Simpan Pemeriksaan</button>
    </div>
</form>
@else
<div class="kepala-aksi">
    <a class="btn btn-garis" href="{{ route('mentor.logbook.index') }}"><x-ikon nama="kembali" /> Kembali ke Daftar</a>
</div>
@endif
@endsection
