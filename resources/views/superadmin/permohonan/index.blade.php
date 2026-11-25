@extends('layouts.app')
@section('judul', 'Permohonan Institusi')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/permohonan.css') }}">
@endpush
@section('konten')
@php
    $kelasStatus = fn (string $s) => match ($s) {
        'Diajukan' => 'status-peringatan',
        'Perlu Perbaikan' => 'status-gagal',
        'Lengkap' => 'status-info',
        'Selesai Diputuskan' => 'status-sukses',
        default => 'status-netral',
    };
    $tautanTab = fn (?string $s) => route('superadmin.permohonan.index', array_filter([
        'status' => $s, 'cari' => $cari ?: null, 'sumber' => request('sumber'),
    ]));
@endphp

<div class="kepala-halaman">
    <div>
        <p class="kicker">Rekrutmen · Jalur Institusi</p>
        <h1>Permohonan Institusi</h1>
        <p class="sub-judul">Catat surat pengajuan magang dari institusi pengirim. Permohonan yang diisi institusi lewat form online juga masuk ke sini.</p>
    </div>
    <div class="kepala-aksi">
        <a class="btn btn-garis" href="{{ route('publik.permohonan.create') }}" target="_blank" rel="noopener"><x-ikon nama="mata" /> Lihat Form Publik</a>
        <a class="btn" href="{{ route('superadmin.permohonan.create') }}"><x-ikon nama="plus" /> Tambah Permohonan</a>
    </div>
</div>

<div class="statistik-grid">
    <div class="stat">
        <span class="ikon-bulat"><x-ikon nama="gedung" /></span>
        <div class="nilai">{{ $ringkasan['total'] }}</div>
        <div class="label">Total permohonan</div>
    </div>
    <div class="stat stat--kuning">
        <span class="ikon-bulat"><x-ikon nama="jam" /></span>
        <div class="nilai">{{ $ringkasan['diajukan'] }}</div>
        <div class="label">Diajukan · menunggu diperiksa</div>
    </div>
    <div class="stat stat--peru">
        <span class="ikon-bulat"><x-ikon nama="kotak-masuk" /></span>
        <div class="nilai">{{ $ringkasan['online'] }}</div>
        <div class="label">Masuk lewat form online</div>
    </div>
</div>

<nav class="tab-bar" aria-label="Filter status">
    <a class="tab {{ $status === null ? 'aktif' : '' }}" href="{{ $tautanTab(null) }}">Semua ({{ $ringkasan['total'] }})</a>
    @foreach (\App\Models\Pengajuan::STATUS as $s)
        <a class="tab {{ $status === $s ? 'aktif' : '' }}" href="{{ $tautanTab($s) }}">{{ $s }} ({{ (int) ($jumlahPerStatus[$s] ?? 0) }})</a>
    @endforeach
</nav>

<form class="kartu filter-bar filter-bar--3" method="GET" action="{{ route('superadmin.permohonan.index') }}">
    @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
    <label>Cari institusi / nomor surat
        <span class="input-ikon">
            <x-ikon nama="cari" />
            <input name="cari" value="{{ $cari }}" placeholder="Ketik nama institusi atau nomor surat">
        </span>
    </label>
    <label>Sumber
        <select name="sumber">
            <option value="">Semua sumber</option>
            <option value="admin" @selected(request('sumber') === 'admin')>Dicatat Superadmin</option>
            <option value="online" @selected(request('sumber') === 'online')>Form online institusi</option>
        </select>
    </label>
    <div class="aksi">
        <a class="btn btn-halus" href="{{ route('superadmin.permohonan.index') }}"><x-ikon nama="atur-ulang" /> Atur ulang</a>
        <button class="btn"><x-ikon nama="filter" /> Terapkan</button>
    </div>
</form>

<div class="kartu kartu-tabel">
    <div class="tabel-wadah">
    <table>
        <thead><tr><th>Institusi</th><th>Tanggal surat</th><th>Peserta</th><th>Sumber</th><th>Status</th><th class="aksi-tabel">Aksi</th></tr></thead>
        <tbody>
        @forelse ($permohonan as $p)
            <tr>
                <td>
                    <div class="sel-utama">
                        <span class="inisial v{{ $loop->index % 5 }}"><x-ikon nama="gedung" /></span>
                        <div>
                            <b>{{ $p->nama_institusi }}</b>
                            <small>No. {{ $p->nomor_surat }} · {{ $p->kode_pengajuan }}</small>
                        </div>
                    </div>
                </td>
                <td><span class="sel-ikon"><x-ikon nama="kalender" />{{ $p->tanggal_surat?->format('d/m/Y') ?? '—' }}</span></td>
                <td><span class="angka-pil">{{ $p->calon_peserta_count }}</span> <small class="redup">peserta</small></td>
                <td>
                    @if ($p->dicatat_oleh)
                        <span class="chip">Dicatat admin</span>
                    @else
                        <span class="chip chip-hijau">Form online</span>
                    @endif
                </td>
                <td><span class="status {{ $kelasStatus($p->status_pengajuan) }}">{{ $p->status_pengajuan }}</span></td>
                <td class="aksi-tabel"><a class="btn btn-garis btn-kecil" href="{{ route('superadmin.permohonan.show', $p) }}"><x-ikon nama="mata" /> Detail</a></td>
            </tr>
        @empty
            <tr><td colspan="6">
                <div class="kosong">
                    <div class="ilustrasi"><x-ikon nama="gedung" /></div>
                    @if ($cari !== '' || $status || request('sumber'))
                        <b>Tidak ada permohonan yang cocok.</b>
                        Coba ubah kata kunci atau filter.
                    @else
                        <b>Belum ada permohonan institusi.</b>
                        Catat surat pengajuan pertama, atau bagikan tautan form online ke institusi.
                        <br><a class="btn" href="{{ route('superadmin.permohonan.create') }}"><x-ikon nama="plus" /> Tambah Permohonan</a>
                    @endif
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    {{ $permohonan->links() }}
</div>

<div class="info-kotak tautan-bagikan">
    <x-ikon nama="info" />
    <div>
        <p><b>Tautan form online untuk institusi:</b> <code>{{ route('publik.permohonan.create') }}</code></p>
        <p class="redup kecil">Bagikan tautan ini ke kampus/sekolah. Permohonan yang masuk otomatis berstatus <b>Diajukan</b> dan muncul di daftar ini.</p>
    </div>
</div>
@endsection
