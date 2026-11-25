@extends('layouts.app')
@section('judul', 'Evaluasi Peserta')
@section('konten')
@php
    $statusEvaluasiKelas = [
        'Memenuhi' => 'status-sukses',
        'Sudah Dinilai' => 'status-sukses',
        'Belum Dinilai' => 'status-peringatan',
        'Belum Memenuhi' => 'status-gagal',
    ];
    $statusMagangKelas = [
        'Aktif' => 'status-sukses',
        'Selesai' => 'status-info',
        'Belum Mulai' => 'status-peringatan',
        'Dihentikan' => 'status-gagal',
    ];
    $adaFilter = request()->filled('cari') || request()->filled('jalur') || request()->filled('status');
    $opsiStatus = ['Belum Dievaluasi', 'Memenuhi', 'Belum Memenuhi', 'Belum Dinilai', 'Sudah Dinilai'];
@endphp

<div class="kepala-halaman">
    <div>
        <p class="kicker">Pembimbingan · UC-13</p>
        <h1>Evaluasi Peserta</h1>
        <p class="sub-judul">Catat status pemenuhan tugas jalur Rekrutmen DTI serta unggah dokumen penilaian jalur Institusi.</p>
    </div>
</div>

<div class="statistik-grid">
    <div class="stat">
        <span class="ikon-bulat"><x-ikon nama="pengguna" /></span>
        <div class="nilai">{{ $ringkasan['total'] }}</div>
        <div class="label">Total peserta bimbingan</div>
    </div>
    <div class="stat stat--olive">
        <span class="ikon-bulat"><x-ikon nama="centang" /></span>
        <div class="nilai">{{ $ringkasan['aktif'] }}</div>
        <div class="label">Peserta berstatus Aktif</div>
    </div>
    <div class="stat">
        <span class="ikon-bulat"><x-ikon nama="medali" /></span>
        <div class="nilai">{{ $ringkasan['selesai_dinilai'] }}</div>
        <div class="label">Memenuhi / Sudah Dinilai</div>
    </div>
    <div class="stat stat--kuning">
        <span class="ikon-bulat"><x-ikon nama="jam" /></span>
        <div class="nilai">{{ $ringkasan['belum_tuntas'] }}</div>
        <div class="label">Belum dievaluasi / belum tuntas</div>
    </div>
</div>

<form class="kartu filter-bar" method="GET" action="{{ route('mentor.evaluasi.index') }}">
    <label>Cari nama / nomor induk / institusi
        <span class="input-ikon">
            <x-ikon nama="cari" />
            <input name="cari" value="{{ request('cari') }}" placeholder="Ketik nama, NIM/NIS, atau institusi">
        </span>
    </label>
    <label>Jalur magang
        <select name="jalur">
            <option value="">Semua jalur</option>
            <option value="DTI" @selected(request('jalur') === 'DTI')>Rekrutmen DTI</option>
            <option value="INSTITUSI" @selected(request('jalur') === 'INSTITUSI')>Institusi</option>
        </select>
    </label>
    <label>Status evaluasi
        <select name="status">
            <option value="">Semua status</option>
            @foreach ($opsiStatus as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
            @endforeach
        </select>
    </label>
    <div class="aksi">
        <a class="btn btn-halus" href="{{ route('mentor.evaluasi.index') }}" title="Atur ulang"><x-ikon nama="atur-ulang" /> Atur ulang</a>
        <button class="btn"><x-ikon nama="filter" /> Terapkan</button>
    </div>
</form>

<div class="kartu kartu-tabel">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat"><x-ikon nama="medali" /></span>
            <div>
                <h2>Daftar peserta bimbingan</h2>
                <p>Menampilkan {{ $pesertaBimbingan->count() }} dari {{ $ringkasan['total'] }} peserta bimbingan Anda.</p>
            </div>
        </div>
    </div>
    <div class="tabel-wadah">
        <table>
            <thead>
                <tr>
                    <th>Peserta</th>
                    <th>Jalur &amp; Institusi</th>
                    <th>Status Magang</th>
                    <th>Ringkasan Tugas</th>
                    <th>Status Evaluasi</th>
                    <th>Waktu Evaluasi</th>
                    <th class="aksi-tabel">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($pesertaBimbingan as $p)
                @php
                    $calon = $p->calonPeserta();
                    $pengajuan = $calon?->pengajuan;
                    $jalur = $pengajuan?->jalur;
                    $institusi = $calon?->institusi_asal ?? $pengajuan?->nama_institusi ?? '—';
                    $evaluasi = $p->evaluasiPeserta;
                @endphp
                <tr>
                    <td>
                        <div class="sel-utama">
                            <span class="inisial v{{ $loop->index % 5 }}">{{ mb_strtoupper(mb_substr($p->pengguna->nama_lengkap, 0, 1)) }}</span>
                            <div>
                                <b>{{ $p->pengguna->nama_lengkap }}</b>
                                <small>{{ $calon?->nomor_induk_pendidikan ?? $p->pengguna->email }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="chip {{ $jalur === 'DTI' ? 'chip-hijau' : '' }}">
                            {{ $jalur === 'DTI' ? 'Rekrutmen DTI' : ($jalur === 'INSTITUSI' ? 'Institusi' : '—') }}
                        </span>
                        <div class="redup kecil" style="margin-top:4px">{{ $institusi }}</div>
                    </td>
                    <td><span class="status {{ $statusMagangKelas[$p->status_magang] ?? 'status-netral' }}">{{ $p->status_magang }}</span></td>
                    <td><span class="angka-pil">{{ $p->tugas_selesai_count }}/{{ $p->tugas_count }} selesai</span></td>
                    <td>
                        @if ($evaluasi)
                            <span class="status {{ $statusEvaluasiKelas[$evaluasi->status_evaluasi] ?? 'status-netral' }}">{{ $evaluasi->status_evaluasi }}</span>
                        @else
                            <span class="status status-netral">Belum Dievaluasi</span>
                        @endif
                    </td>
                    <td>
                        @if ($evaluasi?->waktu_evaluasi)
                            <span class="sel-ikon"><x-ikon nama="jam" /> {{ $evaluasi->waktu_evaluasi->translatedFormat('d M Y, H:i') }}</span>
                        @else
                            <span class="redup">—</span>
                        @endif
                    </td>
                    <td class="aksi-tabel">
                        @if ($p->status_magang === 'Aktif')
                            @if ($evaluasi)
                                <a class="btn btn-garis btn-kecil" href="{{ route('mentor.evaluasi.edit', $p) }}"><x-ikon nama="ubah" /> Ubah</a>
                            @else
                                <a class="btn btn-kecil" href="{{ route('mentor.evaluasi.edit', $p) }}"><x-ikon nama="ubah" /> Catat Evaluasi</a>
                            @endif
                        @else
                            <span class="chip">Baca-saja</span>
                            @if ($evaluasi?->path_dokumen_evaluasi)
                                <a class="btn btn-garis btn-kecil" href="{{ asset('storage/'.$evaluasi->path_dokumen_evaluasi) }}" target="_blank" rel="noopener"><x-ikon nama="berkas" /> Berkas</a>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">
                    <div class="kosong">
                        <div class="ilustrasi"><x-ikon nama="{{ $adaFilter ? 'cari' : 'pengguna' }}" /></div>
                        <b>{{ $adaFilter ? 'Tidak ada peserta yang cocok.' : 'Belum ada peserta bimbingan.' }}</b>
                        {{ $adaFilter ? 'Coba ubah kata kunci pencarian atau pilih ulang filter jalur dan status.' : 'Peserta bimbingan yang telah ditempatkan oleh Kepala Seksi akan tampil di sini.' }}
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
