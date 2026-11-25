@extends('layouts.app')
@section('judul', 'Daftar Tugas')
@section('konten')
<div class="kepala-halaman">
    <div>
        <p class="kicker">Kegiatan Magang</p>
        <h1>Tugas & Proyek Saya</h1>
        <p class="sub-judul">Daftar penugasan dari mentor pembimbing. Serahkan progres dan periksa hasil evaluasi mentor.</p>
    </div>
</div>

<div class="statistik-grid">
    <div class="stat">
        <span class="ikon-bulat"><x-ikon nama="tugas" /></span>
        <div class="nilai">{{ $totalTugas }}</div>
        <div class="label">Total Tugas</div>
    </div>
    <div class="stat stat--olive">
        <span class="ikon-bulat olive"><x-ikon nama="jam" /></span>
        <div class="nilai">{{ $tugasDitugaskan }}</div>
        <div class="label">Ditugaskan</div>
    </div>
    <div class="stat stat--kuning">
        <span class="ikon-bulat kuning"><x-ikon nama="papan-centang" /></span>
        <div class="nilai">{{ $tugasDiserahkan }}</div>
        <div class="label">Diserahkan</div>
    </div>
    <div class="stat stat--peru">
        <span class="ikon-bulat peru"><x-ikon nama="centang" /></span>
        <div class="nilai">{{ $tugasSelesai }}</div>
        <div class="label">Selesai</div>
    </div>
</div>

<div class="kartu kartu-tabel">
    <div class="kartu-kepala">
        <div class="tab-bar">
            <a href="{{ route('peserta.tugas.index') }}" class="tab {{ !request('status') ? 'aktif' : '' }}">Semua ({{ $totalTugas }})</a>
            <a href="{{ route('peserta.tugas.index', ['status' => 'Ditugaskan']) }}" class="tab {{ request('status') === 'Ditugaskan' ? 'aktif' : '' }}">Ditugaskan ({{ $tugasDitugaskan }})</a>
            <a href="{{ route('peserta.tugas.index', ['status' => 'Diserahkan']) }}" class="tab {{ request('status') === 'Diserahkan' ? 'aktif' : '' }}">Diserahkan ({{ $tugasDiserahkan }})</a>
            <a href="{{ route('peserta.tugas.index', ['status' => 'Perlu Perbaikan']) }}" class="tab {{ request('status') === 'Perlu Perbaikan' ? 'aktif' : '' }}">Perlu Perbaikan ({{ $tugasPerluPerbaikan }})</a>
            <a href="{{ route('peserta.tugas.index', ['status' => 'Selesai']) }}" class="tab {{ request('status') === 'Selesai' ? 'aktif' : '' }}">Selesai ({{ $tugasSelesai }})</a>
        </div>
        <form method="GET" action="{{ route('peserta.tugas.index') }}" style="display:inline-flex; gap:8px;">
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul tugas..." style="margin-top:0; width:220px;">
            <button class="btn btn-kecil btn-garis" type="submit"><x-ikon nama="cari" /> Cari</button>
        </form>
    </div>

    <div class="tabel-wadah">
        <table>
            <thead>
                <tr>
                    <th>Judul Tugas</th>
                    <th>Mentor Pembimbing</th>
                    <th>Tenggat</th>
                    <th>Progres</th>
                    <th>Status</th>
                    <th class="aksi-tabel">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($daftarTugas as $tugas)
                @php
                    $namaMentor = $tugas->pesertaMagang?->mentor?->nama_lengkap ?? 'Mentor';
                    $urutanAktif = $tugas->urutanProgresTerakhir();
                    $kelasStatus = match ($tugas->status) {
                        'Diserahkan' => 'status-peringatan',
                        'Selesai' => 'status-sukses',
                        'Perlu Perbaikan' => 'status-gagal',
                        default => 'status-netral',
                    };
                @endphp
                <tr>
                    <td>
                        <div class="sel-utama">
                            <span class="inisial v{{ $loop->index % 5 }}"><x-ikon nama="tugas" /></span>
                            <div>
                                <b>{{ $tugas->judul }}</b>
                                <small class="redup" style="display:block; max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $tugas->deskripsi }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <b>{{ $namaMentor }}</b>
                    </td>
                    <td>
                        <span class="sel-ikon"><x-ikon nama="kalender" />{{ $tugas->tenggat?->format('d/m/Y') }}</span>
                    </td>
                    <td>
                        <span class="chip">Progres ke-{{ $urutanAktif }} dari {{ $tugas->jumlah_progres }}</span>
                    </td>
                    <td>
                        <span class="status {{ $kelasStatus }}">{{ $tugas->status }}</span>
                        @if ($tugas->status === 'Selesai' && $tugas->nilai !== null)
                            <div style="margin-top: 4px;">
                                <span class="chip" style="font-size: 11px; font-weight: 700; color: var(--sukses);">
                                    <x-ikon nama="medali" style="width: 12px; height: 12px; vertical-align: -1px;" /> Nilai: {{ $tugas->nilai }}
                                </span>
                            </div>
                        @endif
                    </td>
                    <td class="aksi-tabel">
                        <a class="btn btn-garis btn-kecil" href="{{ route('peserta.tugas.show', $tugas) }}">
                            <x-ikon nama="mata" /> Detail & Progres
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="kosong">
                            <div class="ilustrasi"><x-ikon nama="tugas" /></div>
                            <b>Belum ada tugas</b>
                            Anda belum memiliki tugas yang diberikan oleh mentor pembimbing.
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $daftarTugas->links() }}
</div>
@endsection
