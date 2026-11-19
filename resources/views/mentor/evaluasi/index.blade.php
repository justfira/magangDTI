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
@endphp
<div class="judul-aksi">
    <h1>Evaluasi Peserta</h1>
</div>

<div class="kartu">
    <h2>Daftar peserta bimbingan</h2>
    <table>
        <thead>
            <tr>
                <th>Peserta</th>
                <th>Jalur &amp; Institusi</th>
                <th>Status Magang</th>
                <th>Ringkasan Tugas</th>
                <th>Status Evaluasi</th>
                <th>Waktu Evaluasi</th>
                <th></th>
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
                    <div>{{ $p->pengguna->nama_lengkap }}</div>
                    @if ($calon?->nomor_induk_pendidikan)
                        <div class="redup kecil">{{ $calon->nomor_induk_pendidikan }}</div>
                    @endif
                </td>
                <td>
                    <div>{{ $jalur === 'DTI' ? 'Rekrutmen DTI' : ($jalur === 'INSTITUSI' ? 'Institusi' : '—') }}</div>
                    <div class="redup kecil">{{ $institusi }}</div>
                </td>
                <td><span class="status {{ $statusMagangKelas[$p->status_magang] ?? 'status-netral' }}">{{ $p->status_magang }}</span></td>
                <td>{{ $p->tugas_selesai_count }}/{{ $p->tugas_count }} selesai</td>
                <td>
                    @if ($evaluasi)
                        <span class="status {{ $statusEvaluasiKelas[$evaluasi->status_evaluasi] ?? 'status-netral' }}">{{ $evaluasi->status_evaluasi }}</span>
                    @else
                        <span class="status status-netral">Belum Dievaluasi</span>
                    @endif
                </td>
                <td class="redup">{{ $evaluasi?->waktu_evaluasi?->translatedFormat('d M Y, H:i') ?? '—' }}</td>
                <td class="aksi-tabel">
                    @if ($p->status_magang === 'Aktif')
                        <a href="{{ route('mentor.evaluasi.edit', $p) }}"><b>{{ $evaluasi ? 'Ubah' : 'Catat Evaluasi' }}</b></a>
                    @else
                        <span class="redup">Baca-saja</span>
                        @if ($evaluasi?->path_dokumen_evaluasi)
                            &nbsp;<a href="{{ asset('storage/'.$evaluasi->path_dokumen_evaluasi) }}" target="_blank" rel="noopener">Berkas</a>
                        @endif
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="redup">Belum ada peserta bimbingan yang ditempatkan kepada Anda.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
