@extends('layouts.app')
@section('judul', 'Permohonan Institusi')
@section('konten')

{{-- ===== KEPALA HALAMAN ===== --}}
<div class="kepala-halaman">
    <div>
        <p class="kicker">Pengajuan Masuk</p>
        <h1>
            Permohonan Institusi
            @if ($jumlahBaru > 0)
                <span class="angka-pil" title="{{ $jumlahBaru }} permohonan berstatus Diajukan">{{ $jumlahBaru }}</span>
            @endif
        </h1>
        <p class="sub-judul">
            Kelola permohonan magang yang masuk dari institusi melalui surat resmi.
            Permohonan <b>Diajukan</b> memerlukan verifikasi dokumen oleh Superadmin.
        </p>
    </div>
    <div class="kepala-aksi">
        <a class="btn" href="{{ route('superadmin.permohonan.create') }}">
            <x-ikon nama="plus" /> Tambah Permohonan
        </a>
    </div>
</div>

{{-- ===== FILTER & PENCARIAN ===== --}}
<form class="kartu filter-bar" method="GET" action="{{ route('superadmin.permohonan.index') }}">
    <label>Cari permohonan
        <span class="input-ikon">
            <x-ikon nama="cari" />
            <input name="cari"
                   value="{{ $cari }}"
                   placeholder="Kode, nama institusi, atau nomor surat"
                   autocomplete="off">
        </span>
    </label>
    <label>Status
        <select name="status">
            <option value="">Semua status</option>
            @foreach ($daftarStatus as $s)
                <option value="{{ $s }}" @selected($statusAktif === $s)>{{ $s }}</option>
            @endforeach
        </select>
    </label>
    <div class="aksi">
        @if ($cari !== '' || $statusAktif)
            <a class="btn btn-halus"
               href="{{ route('superadmin.permohonan.index') }}"
               title="Atur ulang filter">
                <x-ikon nama="atur-ulang" /> Atur ulang
            </a>
        @endif
        <button class="btn"><x-ikon nama="filter" /> Terapkan</button>
    </div>
</form>

{{-- ===== TABEL ===== --}}
<div class="kartu kartu-tabel">

    @if ($cari !== '' || $statusAktif)
        <div class="kartu-kepala" style="padding:14px 22px 0">
            <div class="kartu-judul">
                <span class="ikon-bulat"><x-ikon nama="filter" /></span>
                <div>
                    <h2 style="font-size:14.5px;margin:0">Hasil filter</h2>
                    <p style="margin:0;font-size:12.5px;color:var(--redup)">
                        {{ $permohonan->total() }} permohonan ditemukan
                        @if ($cari !== '') &middot; pencarian &ldquo;<b>{{ $cari }}</b>&rdquo; @endif
                        @if ($statusAktif) &middot; status <b>{{ $statusAktif }}</b> @endif
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="tabel-wadah">
        <table>
            <thead>
                <tr>
                    <th>Kode Pengajuan</th>
                    <th>Nama Institusi</th>
                    <th>Nomor Surat</th>
                    <th>Tanggal Pengajuan</th>
                    <th style="text-align:center">Calon</th>
                    <th>Status</th>
                    <th>Dicatat Oleh</th>
                    <th class="aksi-tabel">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($permohonan as $p)
                @php
                    $kelasStatus = match ($p->status_pengajuan) {
                        'Diajukan'           => 'status-peringatan',
                        'Perlu Perbaikan'    => 'status-gagal',
                        'Lengkap'            => 'status-sukses',
                        'Selesai Diputuskan' => 'status-netral',
                        default              => 'status-netral',
                    };
                    $inisialTeks = mb_strtoupper(mb_substr($p->nama_institusi ?? 'I', 0, 1));
                    $varianInisial = 'v' . (crc32($p->kode_pengajuan ?? '') % 5);
                @endphp
                <tr>
                    {{-- Kode --}}
                    <td>
                        <div class="sel-ikon">
                            <x-ikon nama="berkas" />
                            <b style="font-family:monospace;font-size:13px;letter-spacing:.5px">
                                {{ $p->kode_pengajuan }}
                            </b>
                        </div>
                    </td>

                    {{-- Institusi --}}
                    <td>
                        <div class="sel-utama">
                            <span class="inisial {{ $varianInisial }}">{{ $inisialTeks }}</span>
                            <div>
                                <b>{{ $p->nama_institusi ?? '—' }}</b>
                                @if ($p->kontak_institusi)
                                    <small>{{ $p->kontak_institusi }}</small>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Nomor Surat --}}
                    <td>
                        <span style="font-size:12.5px">{{ $p->nomor_surat ?? '—' }}</span>
                        @if ($p->tanggal_surat)
                            <small class="redup" style="display:block;font-size:11.5px">
                                Tgl. surat: {{ $p->tanggal_surat->translatedFormat('d M Y') }}
                            </small>
                        @endif
                    </td>

                    {{-- Tanggal Pengajuan --}}
                    <td>
                        <span class="sel-ikon">
                            <x-ikon nama="kalender" />
                            {{ $p->tanggal_pengajuan?->translatedFormat('d M Y') ?? '—' }}
                        </span>
                        @if ($p->tanggal_pengajuan)
                            <small class="redup" style="display:block;margin-top:2px;font-size:11.5px;padding-left:20px">
                                {{ $p->tanggal_pengajuan->diffForHumans() }}
                            </small>
                        @endif
                    </td>

                    {{-- Jumlah Calon --}}
                    <td style="text-align:center">
                        <span class="angka-pil" title="{{ $p->calon_peserta_count }} calon peserta">
                            {{ $p->calon_peserta_count }}
                        </span>
                    </td>

                    {{-- Status --}}
                    <td>
                        <span class="status {{ $kelasStatus }}">{{ $p->status_pengajuan }}</span>
                        @if ($p->pemeriksa?->pengguna && $p->waktu_pemeriksaan)
                            <small class="redup" style="display:block;margin-top:4px;font-size:11.5px">
                                Diperiksa {{ $p->waktu_pemeriksaan->diffForHumans() }}
                            </small>
                        @endif
                    </td>

                    {{-- Dicatat Oleh --}}
                    <td>
                        @if ($p->dicatatOleh?->pengguna)
                            <div class="sel-ikon">
                                <x-ikon nama="pengguna" />
                                {{ $p->dicatatOleh->pengguna->nama_lengkap }}
                            </div>
                        @else
                            <span class="redup">—</span>
                        @endif
                    </td>

                    {{-- Aksi --}}
                    <td class="aksi-tabel">
                        @if (\Illuminate\Support\Facades\Route::has('superadmin.verifikasi.show'))
                            <a class="btn btn-garis btn-kecil"
                               href="{{ route('superadmin.verifikasi.show', $p) }}"
                               title="Periksa / lihat detail pengajuan {{ $p->kode_pengajuan }}">
                                <x-ikon nama="papan-centang" /> Detail
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="kosong">
                            <div class="ilustrasi"><x-ikon nama="gedung" /></div>
                            @if ($cari !== '' || $statusAktif)
                                <b>Tidak ada permohonan yang cocok.</b>
                                Coba ubah kata kunci atau filter pencarian.
                                <br>
                                <a class="btn btn-garis"
                                   href="{{ route('superadmin.permohonan.index') }}"
                                   style="margin-top:14px">
                                    <x-ikon nama="atur-ulang" /> Atur ulang filter
                                </a>
                            @else
                                <b>Belum ada permohonan institusi.</b>
                                Tambahkan permohonan dari surat yang diterima secara manual.
                                <br>
                                <a class="btn" href="{{ route('superadmin.permohonan.create') }}" style="margin-top:14px">
                                    <x-ikon nama="plus" /> Tambah Permohonan
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($permohonan->hasPages())
        <div style="padding:12px 22px 16px;border-top:1px solid var(--garis)">
            {{ $permohonan->links() }}
        </div>
    @endif

</div>

<p class="petunjuk">
    <x-ikon nama="info" style="width:14px;height:14px;vertical-align:middle;" />
    Kolom <b>Calon</b> menunjukkan jumlah calon peserta dalam permohonan.
    Klik <b>Detail</b> untuk memeriksa kelengkapan dokumen di halaman Verifikasi Pengajuan.
</p>

@endsection
