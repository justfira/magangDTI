@extends('layouts.app')
@section('judul', 'Penyelesaian Magang')
@section('konten')
<div class="kepala-halaman">
    <h1>Penyelesaian Magang</h1>
    <p class="sub-judul">Tinjau kegiatan peserta di seksi Anda, lalu beri rekomendasi penyelesaian.</p>
</div>

<div class="tab-bar">
    @foreach ($daftarTab as $kunci => $label)
        <a class="tab {{ $tab === $kunci ? 'aktif' : '' }}" href="{{ route('kasi.penyelesaian.index', ['tab' => $kunci]) }}">{{ $label }}</a>
    @endforeach
</div>

<div class="kartu">
@if ($peserta->isEmpty())
    <div class="kosong">
        <b>Belum ada peserta</b>
        Tidak ada peserta {{ $tab === 'selesai' ? 'yang sudah selesai' : 'yang sedang magang' }} di seksi Anda.
    </div>
@else
<table>
    <thead>
        <tr>
            <th>Peserta</th><th>Periode</th><th>Mentor</th><th>Tugas</th><th>Hadir</th>
            <th>Logbook</th><th>Evaluasi</th><th>Penyelesaian</th><th></th>
        </tr>
    </thead>
    <tbody>
    @foreach ($peserta as $p)
        <tr>
            <td>
                <b>{{ $p->pengguna?->nama_lengkap ?? '-' }}</b><br>
                <small class="redup">{{ $p->keputusan?->calon?->institusi_asal ?? '-' }}</small>
            </td>
            <td>{{ $p->tanggal_mulai?->format('d/m/Y') ?? '-' }} – {{ $p->tanggal_selesai?->format('d/m/Y') ?? '-' }}</td>
            <td>{{ $p->mentor?->pengguna?->nama_lengkap ?? 'Belum ditetapkan' }}</td>
            <td>{{ $p->tugas_selesai_count }}/{{ $p->tugas_count }}</td>
            <td>{{ $p->presensi_count }} hari</td>
            <td>{{ $p->logbook_disetujui_count }}/{{ $p->logbook_count }}</td>
            <td>
                @if ($p->evaluasiPeserta)
                    <span class="status {{ in_array($p->evaluasiPeserta->status_evaluasi, ['Memenuhi', 'Sudah Dinilai']) ? 'status-sukses' : 'status-peringatan' }}">{{ $p->evaluasiPeserta->status_evaluasi }}</span>
                @else
                    <span class="redup">Belum ada</span>
                @endif
            </td>
            <td>@include('kepala-seksi.penyelesaian._status', ['penyelesaian' => $p->penyelesaianMagang->sortByDesc('id_penyelesaian')->first()])</td>
            <td><a class="btn btn-garis btn-kecil" href="{{ route('kasi.penyelesaian.show', $p) }}">Tinjau</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $peserta->links() }}
@endif
</div>
<p class="petunjuk">Tugas dan logbook ditampilkan sebagai jumlah selesai/disetujui dari total. Hadir = jumlah hari presensi tercatat.</p>
@endsection
