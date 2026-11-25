@extends('layouts.app')
@section('judul', 'Verifikasi Pengajuan')
@section('konten')

{{-- ===== KEPALA HALAMAN ===== --}}
<div class="kepala-halaman">
    <div>
        <p class="kicker">Pemeriksaan Dokumen</p>
        <h1>
            Verifikasi Pengajuan
            @if ($jumlahPerlu > 0)
                <span class="angka-pil" title="{{ $jumlahPerlu }} pengajuan menunggu tindakan">{{ $jumlahPerlu }}</span>
            @endif
        </h1>
        <p class="sub-judul">
            Periksa kelengkapan dokumen pengajuan magang jalur DTI dan Institusi.
            Pengajuan berstatus <b>Diajukan</b> dan <b>Perlu Perbaikan</b> memerlukan tindakan.
        </p>
    </div>
</div>

{{-- ===== FILTER & PENCARIAN ===== --}}
<form class="kartu filter-bar" method="GET" action="{{ route('superadmin.verifikasi.index') }}">
    <label>Cari pengajuan
        <span class="input-ikon">
            <x-ikon nama="cari" />
            <input name="cari" value="{{ $cari }}" placeholder="Kode, nama institusi, atau nama pendaftar" autocomplete="off">
        </span>
    </label>
    <label>Jalur
        <select name="jalur">
            <option value="">Semua jalur</option>
            @foreach ($daftarJalur as $j)
                <option value="{{ $j }}" @selected($jalurAktif === $j)>{{ $j }}</option>
            @endforeach
        </select>
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
        @if ($cari !== '' || $jalurAktif || $statusAktif)
            <a class="btn btn-halus" href="{{ route('superadmin.verifikasi.index') }}" title="Atur ulang filter">
                <x-ikon nama="atur-ulang" /> Atur ulang
            </a>
        @endif
        <button class="btn"><x-ikon nama="filter" /> Terapkan</button>
    </div>
</form>

{{-- ===== TABEL ===== --}}
<div class="kartu kartu-tabel">

    @if ($cari !== '' || $jalurAktif || $statusAktif)
        <div class="kartu-kepala" style="padding:14px 22px 0">
            <div class="kartu-judul">
                <span class="ikon-bulat"><x-ikon nama="filter" /></span>
                <div>
                    <h2 style="font-size:14.5px;margin:0">Hasil filter</h2>
                    <p style="margin:0;font-size:12.5px;color:var(--redup)">
                        {{ $pengajuan->total() }} pengajuan ditemukan
                        @if ($cari !== '') &middot; pencarian &ldquo;<b>{{ $cari }}</b>&rdquo; @endif
                        @if ($jalurAktif) &middot; jalur <b>{{ $jalurAktif }}</b> @endif
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
                    <th>Pemohon / Institusi</th>
                    <th>Jalur</th>
                    <th style="text-align:center">Calon</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Pemeriksa</th>
                    <th class="aksi-tabel">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($pengajuan as $p)
                @php
                    $kelasStatus = match ($p->status_pengajuan) {
                        'Diajukan'           => 'status-peringatan',
                        'Perlu Perbaikan'    => 'status-gagal',
                        'Lengkap'            => 'status-sukses',
                        'Selesai Diputuskan' => 'status-netral',
                        default              => 'status-netral',
                    };
                    $kelasJalur    = $p->jalur === 'DTI' ? 'chip-hijau' : '';
                    $namaPemohon   = $p->jalur === 'INSTITUSI'
                        ? ($p->nama_institusi ?? '—')
                        : ($p->diajukanOleh?->nama_lengkap ?? '—');
                    $detailPemohon = $p->jalur === 'INSTITUSI'
                        ? ($p->nomor_surat ?? '')
                        : ($p->program?->nama_program ?? 'Tanpa program');
                    $varianInisial = 'v' . (crc32($p->kode_pengajuan ?? '') % 5);
                    $inisialTeks   = mb_strtoupper(mb_substr(
                        $p->jalur === 'INSTITUSI'
                            ? ($p->nama_institusi ?? 'I')
                            : ($p->diajukanOleh?->nama_lengkap ?? 'D'),
                        0, 1
                    ));
                    $sudahDiperiksa = in_array($p->status_pengajuan, ['Diajukan', 'Perlu Perbaikan']);
                @endphp
                <tr>
                    {{-- Kode Pengajuan --}}
                    <td>
                        <div class="sel-ikon">
                            <x-ikon nama="berkas" />
                            <div>
                                <b style="font-size:13px;font-family:monospace;letter-spacing:.5px">{{ $p->kode_pengajuan }}</b>
                            </div>
                        </div>
                    </td>

                    {{-- Pemohon / Institusi --}}
                    <td>
                        <div class="sel-utama">
                            <span class="inisial {{ $varianInisial }}">{{ $inisialTeks }}</span>
                            <div>
                                <b>{{ $namaPemohon }}</b>
                                <small>{{ $detailPemohon }}</small>
                            </div>
                        </div>
                    </td>

                    {{-- Jalur --}}
                    <td><span class="chip {{ $kelasJalur }}">{{ $p->jalur }}</span></td>

                    {{-- Jumlah Calon --}}
                    <td style="text-align:center">
                        <span class="angka-pil" title="{{ $p->calon_peserta_count }} calon peserta">{{ $p->calon_peserta_count }}</span>
                    </td>

                    {{-- Tanggal --}}
                    <td>
                        <span class="sel-ikon">
                            <x-ikon nama="kalender" />
                            {{ $p->tanggal_pengajuan?->translatedFormat('d M Y') ?? '—' }}
                        </span>
                    </td>

                    {{-- Status --}}
                    <td>
                        <span class="status {{ $kelasStatus }}">{{ $p->status_pengajuan }}</span>
                        @if ($p->waktu_pemeriksaan)
                            <small class="redup" style="display:block;margin-top:4px;font-size:11.5px">
                                {{ $p->waktu_pemeriksaan->diffForHumans() }}
                            </small>
                        @endif
                    </td>

                    {{-- Pemeriksa --}}
                    <td>
                        @if ($p->pemeriksa?->pengguna)
                            <div class="sel-ikon">
                                <x-ikon nama="pengguna" />
                                {{ $p->pemeriksa->pengguna->nama_lengkap }}
                            </div>
                        @else
                            <span class="redup">—</span>
                        @endif
                    </td>

                    {{-- Aksi --}}
                    <td class="aksi-tabel">
                        @if (\Illuminate\Support\Facades\Route::has('superadmin.verifikasi.show'))
                            <a class="btn {{ $sudahDiperiksa ? '' : 'btn-garis' }} btn-kecil"
                               href="{{ route('superadmin.verifikasi.show', $p) }}"
                               title="{{ $sudahDiperiksa ? 'Periksa' : 'Lihat' }} pengajuan {{ $p->kode_pengajuan }}">
                                <x-ikon nama="papan-centang" />
                                {{ $sudahDiperiksa ? 'Periksa' : 'Lihat' }}
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="kosong">
                            <div class="ilustrasi"><x-ikon nama="papan-centang" /></div>
                            @if ($cari !== '' || $jalurAktif || $statusAktif)
                                <b>Tidak ada pengajuan yang cocok.</b>
                                Coba ubah kata kunci atau filter pencarian.
                                <br>
                                <a class="btn btn-garis" href="{{ route('superadmin.verifikasi.index') }}" style="margin-top:14px">
                                    <x-ikon nama="atur-ulang" /> Atur ulang filter
                                </a>
                            @else
                                <b>Belum ada pengajuan.</b>
                                Pengajuan dari pendaftar DTI atau permohonan institusi akan muncul di sini.
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($pengajuan->hasPages())
        <div style="padding:12px 22px 16px;border-top:1px solid var(--garis)">
            {{ $pengajuan->links() }}
        </div>
    @endif

</div>

<p class="petunjuk">
    <x-ikon nama="info" style="width:14px;height:14px;vertical-align:middle;" />
    Kolom <b>Calon</b> menunjukkan jumlah calon peserta dalam pengajuan.
    Urutan tabel: <b>Diajukan</b> dan <b>Perlu Perbaikan</b> ditampilkan lebih dulu karena memerlukan tindakan.
</p>

@endsection
