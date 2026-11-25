@extends('layouts.app')
@section('judul', 'Tugas Peserta Bimbingan')
@section('konten')
<div class="kepala-halaman">
    <div>
        <p class="kicker">Bimbingan Magang</p>
        <h1>Tugas Peserta Bimbingan</h1>
        <p class="sub-judul">Kelola proyek tugas peserta bimbingan, pantau pencapaian progres, dan periksa hasil kerja.</p>
    </div>
    <div class="kepala-aksi">
        <a class="btn" href="{{ route('mentor.tugas.create') }}"><x-ikon nama="plus" /> Buat Tugas</a>
    </div>
</div>

<div class="statistik-grid">
    <div class="stat">
        <span class="ikon-bulat"><x-ikon nama="tugas" /></span>
        <div class="nilai">{{ $totalTugas }}</div>
        <div class="label">Total Tugas</div>
    </div>
    <div class="stat stat--kuning">
        <span class="ikon-bulat kuning"><x-ikon nama="jam" /></span>
        <div class="nilai">{{ $tugasDiserahkan }}</div>
        <div class="label">Menunggu Review</div>
    </div>
    <div class="stat stat--olive">
        <span class="ikon-bulat olive"><x-ikon nama="peringatan" /></span>
        <div class="nilai">{{ $tugasPerluPerbaikan }}</div>
        <div class="label">Perlu Perbaikan</div>
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
            <a href="{{ route('mentor.tugas.index') }}" class="tab {{ !request('status') ? 'aktif' : '' }}">Semua ({{ $totalTugas }})</a>
            <a href="{{ route('mentor.tugas.index', ['status' => 'Diserahkan']) }}" class="tab {{ request('status') === 'Diserahkan' ? 'aktif' : '' }}">Menunggu Review ({{ $tugasDiserahkan }})</a>
            <a href="{{ route('mentor.tugas.index', ['status' => 'Perlu Perbaikan']) }}" class="tab {{ request('status') === 'Perlu Perbaikan' ? 'aktif' : '' }}">Perlu Perbaikan ({{ $tugasPerluPerbaikan }})</a>
            <a href="{{ route('mentor.tugas.index', ['status' => 'Selesai']) }}" class="tab {{ request('status') === 'Selesai' ? 'aktif' : '' }}">Selesai ({{ $tugasSelesai }})</a>
        </div>
        <form method="GET" action="{{ route('mentor.tugas.index') }}" style="display:inline-flex; gap:8px;">
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul atau peserta..." style="margin-top:0; width:220px;">
            <button class="btn btn-kecil btn-garis" type="submit"><x-ikon nama="cari" /> Cari</button>
        </form>
    </div>

    <div class="tabel-wadah">
        <table>
            <thead>
                <tr>
                    <th>Peserta</th>
                    <th>Judul Tugas</th>
                    <th>Tenggat</th>
                    <th>Progres</th>
                    <th>Status</th>
                    <th class="aksi-tabel">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($daftarTugas as $tugas)
                @php
                    $namaPeserta = $tugas->pesertaMagang?->calonPeserta()?->nama_lengkap ?? $tugas->pesertaMagang?->pengguna?->nama_lengkap ?? 'Peserta';
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
                            <span class="inisial v{{ $loop->index % 5 }}"><x-ikon nama="pengguna" /></span>
                            <div>
                                <b>{{ $namaPeserta }}</b>
                                <small class="redup">{{ $tugas->pesertaMagang?->calonPeserta()?->institusi_asal ?? 'DTI Unand' }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <b>{{ $tugas->judul }}</b>
                        <small class="redup" style="display:block; max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $tugas->deskripsi }}</small>
                    </td>
                    <td>
                        <span class="sel-ikon"><x-ikon nama="kalender" />{{ $tugas->tenggat?->format('d/m/Y') }}</span>
                    </td>
                    <td>
                        <span class="chip">Progres ke-{{ $urutanAktif }} dari {{ $tugas->jumlah_progres }}</span>
                    </td>
                    <td>
                        <span class="status {{ $kelasStatus }}">{{ $tugas->status }}</span>
                    </td>
                    <td class="aksi-tabel">
                        <a class="btn btn-garis btn-kecil" href="{{ route('mentor.tugas.show', $tugas) }}" title="Lihat detail &amp; progres">
                            <x-ikon nama="mata" /> Detail
                        </a>
                        <a class="btn btn-garis btn-kecil" href="{{ route('mentor.tugas.edit', $tugas) }}" title="Ubah tugas &amp; tahapan">
                            <x-ikon nama="ubah" /> Ubah
                        </a>
                        <form method="POST" action="{{ route('mentor.tugas.destroy', $tugas) }}" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tugas ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-garis-bahaya btn-kecil" title="Hapus tugas">
                                <x-ikon nama="silang" />
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="kosong">
                            <div class="ilustrasi"><x-ikon nama="tugas" /></div>
                            <b>Belum ada tugas</b>
                            Belum ada tugas yang diberikan kepada peserta bimbingan.
                            <br><a class="btn" href="{{ route('mentor.tugas.create') }}"><x-ikon nama="plus" /> Buat Tugas Pertama</a>
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
