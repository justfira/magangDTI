@extends('layouts.app')
@section('judul', 'Logbook Harian')
@section('konten')
@php
    $statusKelas = ['Dikirim' => 'status-peringatan', 'Disetujui' => 'status-sukses', 'Perlu Perbaikan' => 'status-gagal'];
    $jumlahDisetujui = $logbook->where('status', 'Disetujui')->count();
    $jumlahProses = $logbook->whereIn('status', ['Dikirim', 'Perlu Perbaikan'])->count();
@endphp

<div class="kepala-halaman">
    <div>
        <p class="kicker">Aktivitas Magang · UC-12</p>
        <h1>Logbook Harian</h1>
        <p class="sub-judul">Catat aktivitas harian selama magang di DTI dan pantau hasil pemeriksaan mentor Anda.</p>
    </div>
    <div class="kepala-aksi">
        <a class="btn" href="{{ route('peserta.logbook.create') }}"><x-ikon nama="plus" /> Isi Logbook</a>
    </div>
</div>

<div class="statistik-grid">
    <div class="stat">
        <span class="ikon-bulat"><x-ikon nama="buku" /></span>
        <div class="nilai">{{ $logbook->count() }}</div>
        <div class="label">Total logbook tercatat</div>
    </div>
    <div class="stat">
        <span class="ikon-bulat"><x-ikon nama="centang" /></span>
        <div class="nilai">{{ $jumlahDisetujui }}</div>
        <div class="label">Logbook disetujui</div>
    </div>
    <div class="stat stat--kuning">
        <span class="ikon-bulat"><x-ikon nama="jam" /></span>
        <div class="nilai">{{ $jumlahProses }}</div>
        <div class="label">Menunggu / perlu perbaikan</div>
    </div>
    <div class="stat stat--peru">
        <span class="ikon-bulat"><x-ikon nama="peringatan" /></span>
        <div class="nilai">{{ $hariKosong->count() }}</div>
        <div class="label">Hari hadir belum diisi</div>
    </div>
</div>

@if ($hariKosong->isNotEmpty())
<div class="kartu">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat kuning"><x-ikon nama="peringatan" /></span>
            <div>
                <h2>Hari hadir tanpa logbook</h2>
                <p>Tanggal berikut tercatat hadir tetapi belum memiliki catatan logbook harian.</p>
            </div>
        </div>
        <a class="btn btn-garis btn-kecil" href="{{ route('peserta.logbook.create') }}"><x-ikon nama="plus" /> Lengkapi Sekarang</a>
    </div>
    <div class="info-kotak">
        <x-ikon nama="info" />
        <p>Tanggal dengan izin ketidakhadiran penuh berstatus <b>Disetujui</b> dikecualikan dari kewajiban pengisian logbook.</p>
    </div>
    <div class="chip-grup">
        @foreach ($hariKosong as $presensiHari)
            <span class="chip"><x-ikon nama="kalender" /> {{ $presensiHari->tanggal->translatedFormat('d M Y') }}</span>
        @endforeach
    </div>
</div>
@endif

<div class="kartu kartu-tabel">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat"><x-ikon nama="buku" /></span>
            <div>
                <h2>Riwayat logbook harian</h2>
                <p>Satu logbook dicatat untuk setiap tanggal kegiatan selama periode magang.</p>
            </div>
        </div>
    </div>
    <div class="tabel-wadah">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Uraian Aktivitas</th>
                    <th>Status</th>
                    <th>Diperiksa</th>
                    <th class="aksi-tabel">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($logbook as $l)
                <tr>
                    <td><span class="sel-ikon"><x-ikon nama="kalender" /> <b>{{ $l->tanggal_kegiatan->translatedFormat('d M Y') }}</b></span></td>
                    <td>{{ \Illuminate\Support\Str::limit($l->uraian_aktivitas, 80) }}</td>
                    <td><span class="status {{ $statusKelas[$l->status] ?? 'status-netral' }}">{{ $l->status }}</span></td>
                    <td>
                        @if ($l->waktu_periksa)
                            <span class="sel-ikon"><x-ikon nama="jam" /> {{ $l->waktu_periksa->format('d/m H:i') }}</span>
                        @else
                            <span class="redup">—</span>
                        @endif
                    </td>
                    <td class="aksi-tabel">
                        <a class="btn btn-garis btn-kecil" href="{{ route('peserta.logbook.show', $l) }}"><x-ikon nama="mata" /> Detail</a>
                        @if ($l->status === 'Perlu Perbaikan')
                            <a class="btn btn-kecil" href="{{ route('peserta.logbook.edit', $l) }}"><x-ikon nama="ubah" /> Perbaiki</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">
                    <div class="kosong">
                        <div class="ilustrasi"><x-ikon nama="buku" /></div>
                        <b>Belum ada logbook harian.</b>
                        Mulai catat aktivitas magang Anda agar dapat diperiksa oleh mentor pembimbing.
                        <br><a class="btn" href="{{ route('peserta.logbook.create') }}"><x-ikon nama="plus" /> Isi Logbook</a>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
