@extends('layouts.app')
@section('judul', 'Penempatan Peserta Magang')
@section('konten')

{{-- ===== KEPALA HALAMAN ===== --}}
<div class="kepala-halaman">
    <div>
        <p class="kicker">UC-07 &mdash; Seksi {{ $seksiKasi?->nama_seksi ?? '—' }}</p>
        <h1>
            Penempatan Peserta Magang
            @if ($jumlahMenunggu > 0)
                <span class="angka-pil" title="{{ $jumlahMenunggu }} peserta menunggu penempatan">{{ $jumlahMenunggu }}</span>
            @endif
        </h1>
        <p class="sub-judul">
            Tempatkan peserta magang yang sudah diaktifkan ke
            <b>{{ $seksiKasi?->nama_seksi ?? 'seksi Anda' }}</b>
            dan pilih mentor pembimbing dari seksi yang sama.
        </p>
    </div>
</div>

{{-- ===== FLASH MESSAGE ===== --}}
@if (session('sukses'))
    <div class="alert alert-sukses" role="alert">{{ session('sukses') }}</div>
@endif
@if (session('gagal'))
    <div class="alert alert-gagal" role="alert">{{ session('gagal') }}</div>
@endif

{{-- ===== TAB ===== --}}
<div class="tab-bar">
    @foreach ($daftarTab as $kunci => $label)
        <a class="tab {{ $tab === $kunci ? 'aktif' : '' }}"
           href="{{ route('kasi.penempatan.index', array_filter(['tab' => $kunci, 'cari' => $cari])) }}">
            {{ $label }}
            @if ($kunci === 'menunggu' && $jumlahMenunggu > 0)
                <span class="badge-tab">{{ $jumlahMenunggu }}</span>
            @endif
        </a>
    @endforeach
</div>

{{-- ===== PENCARIAN ===== --}}
<form class="kartu filter-bar" method="GET" action="{{ route('kasi.penempatan.index') }}">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <label>Cari peserta
        <span class="input-ikon">
            <x-ikon nama="cari" />
            <input name="cari"
                   value="{{ $cari }}"
                   placeholder="Nama peserta atau institusi asal"
                   autocomplete="off">
        </span>
    </label>
    <div class="aksi">
        @if ($cari !== '')
            <a class="btn btn-halus" href="{{ route('kasi.penempatan.index', ['tab' => $tab]) }}">
                <x-ikon nama="atur-ulang" /> Atur ulang
            </a>
        @endif
        <button class="btn"><x-ikon nama="cari" /> Cari</button>
    </div>
</form>

{{-- ===== TABEL ===== --}}
<div class="kartu kartu-tabel">

    @if ($cari !== '')
        <div class="kartu-kepala" style="padding:12px 22px 0">
            <div class="kartu-judul">
                <span class="ikon-bulat"><x-ikon nama="cari" /></span>
                <div>
                    <h2 style="font-size:14px;margin:0">Hasil pencarian</h2>
                    <p style="margin:0;font-size:12.5px;color:var(--redup)">
                        {{ $peserta->total() }} peserta ditemukan
                        untuk &ldquo;<b>{{ $cari }}</b>&rdquo;
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="tabel-wadah">
        <table>
            <thead>
                <tr>
                    <th>Peserta</th>
                    <th>Institusi / Program</th>
                    <th>Jalur</th>
                    <th>Periode Magang</th>
                    @if ($tab === 'riwayat')
                        <th>Mentor</th>
                        <th>Tanggal Penempatan</th>
                        <th>Status</th>
                    @else
                        <th class="aksi-tabel">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>

            @forelse ($peserta as $pm)
                @php
                    $calon   = $pm->keputusan?->calon;
                    $pengaj  = $calon?->pengajuan;
                    $jalur   = $pengaj?->jalur ?? '—';

                    $labelSumber = $jalur === 'INSTITUSI'
                        ? ($pengaj?->nama_institusi ?? $calon?->institusi_asal ?? '—')
                        : ($pengaj?->diajukanOleh?->nama_lengkap ?? '—');

                    $kelasStatus = match ($pm->status_magang) {
                        'Belum Mulai' => 'status-netral',
                        'Aktif'       => 'status-sukses',
                        'Selesai'     => 'status-sukses',
                        'Dihentikan'  => 'status-gagal',
                        default       => 'status-netral',
                    };
                @endphp

                <tr>
                    {{-- Peserta --}}
                    <td>
                        <div class="sel-utama">
                            <span class="inisial v{{ abs(crc32($calon?->nama_lengkap ?? '')) % 5 }}">
                                {{ mb_strtoupper(mb_substr($calon?->nama_lengkap ?? '?', 0, 1)) }}
                            </span>
                            <div>
                                <b>{{ $calon?->nama_lengkap ?? '—' }}</b>
                                @if ($calon?->nomor_induk_pendidikan)
                                    <small class="redup">{{ $calon->nomor_induk_pendidikan }}</small>
                                @endif
                                @if ($pm->pengguna)
                                    <small class="redup" style="display:block">{{ $pm->pengguna->email }}</small>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Institusi / Program --}}
                    <td>
                        <span>{{ $labelSumber }}</span>
                        @if ($calon?->jurusan_prodi)
                            <small class="redup" style="display:block">{{ $calon->jurusan_prodi }}</small>
                        @endif
                    </td>

                    {{-- Jalur --}}
                    <td>
                        <span class="status {{ $jalur === 'INSTITUSI' ? 'status-info' : 'status-netral' }}">
                            {{ $jalur }}
                        </span>
                    </td>

                    {{-- Periode --}}
                    <td>
                        <span style="font-size:13px;white-space:nowrap">
                            {{ $pm->tanggal_mulai?->format('d M Y') ?? '—' }}
                        </span>
                        <small class="redup" style="display:block;white-space:nowrap">
                            s.d. {{ $pm->tanggal_selesai?->format('d M Y') ?? '—' }}
                        </small>
                    </td>

                    @if ($tab === 'riwayat')
                        {{-- Mentor --}}
                        <td>
                            @if ($pm->mentor?->pengguna)
                                <b>{{ $pm->mentor->pengguna->nama_lengkap }}</b>
                                @if ($pm->ditetapkanOleh?->pengguna)
                                    <small class="redup" style="display:block">
                                        oleh {{ $pm->ditetapkanOleh->pengguna->nama_lengkap }}
                                    </small>
                                @endif
                            @else
                                <span class="redup">—</span>
                            @endif
                        </td>

                        {{-- Tanggal Penempatan --}}
                        <td>
                            <span style="font-size:13px">
                                {{ $pm->tanggal_penempatan?->format('d M Y') ?? '—' }}
                            </span>
                        </td>

                        {{-- Status --}}
                        <td>
                            <span class="status {{ $kelasStatus }}">{{ $pm->status_magang }}</span>
                        </td>

                    @else
                        {{-- Aksi: Atur Penempatan --}}
                        <td class="aksi-tabel">
                            <a class="btn btn-kecil"
                               href="{{ route('kasi.penempatan.edit', $pm) }}">
                                <x-ikon nama="pensil" /> Atur Penempatan
                            </a>
                        </td>
                    @endif

                </tr>

            @empty
                <tr>
                    <td colspan="{{ $tab === 'riwayat' ? 7 : 5 }}" class="kosong">
                        <div class="empty-state">
                            <x-ikon nama="{{ $tab === 'menunggu' ? 'centang-lingkaran' : 'berkas' }}" />
                            <p>
                                @if ($cari !== '')
                                    Tidak ada peserta ditemukan untuk pencarian
                                    &ldquo;<b>{{ $cari }}</b>&rdquo;.
                                @elseif ($tab === 'menunggu')
                                    Tidak ada peserta yang menunggu penempatan saat ini.
                                    <small class="redup" style="display:block;margin-top:4px">
                                        Peserta akan muncul di sini setelah diaktifkan oleh Superadmin melalui UC-06.
                                    </small>
                                @else
                                    Belum ada peserta yang ditempatkan di
                                    <b>{{ $seksiKasi?->nama_seksi ?? 'seksi Anda' }}</b>.
                                @endif
                            </p>
                        </div>
                    </td>
                </tr>
            @endforelse

            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($peserta->hasPages())
        <div class="pagination-bar">
            {{ $peserta->links() }}
        </div>
    @endif

</div>

{{-- ===== CSS TAMBAHAN ===== --}}
<style>
.alert {
    padding: 12px 18px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 14px;
    line-height: 1.6;
}
.alert-sukses {
    background: rgba(22,101,52,.08);
    border-left: 4px solid var(--utama, #166534);
    color: #14532d;
}
.alert-gagal {
    background: rgba(220,38,38,.07);
    border-left: 4px solid var(--gagal, #dc2626);
    color: #991b1b;
}
.status-info { background: rgba(37,99,235,.1); color: #1d4ed8; }
.badge-tab {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 18px;
    padding: 0 5px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    background: var(--utama, #166534);
    color: #fff;
    margin-left: 6px;
    vertical-align: middle;
}
.tab.aktif .badge-tab { background: #fff; color: var(--utama, #166534); }
</style>

@endsection
