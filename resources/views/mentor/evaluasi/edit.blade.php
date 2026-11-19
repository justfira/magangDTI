@extends('layouts.app')
@section('judul', 'Catat Evaluasi')
@section('konten')
@php
    $statusEvaluasiKelas = [
        'Memenuhi' => 'status-sukses',
        'Sudah Dinilai' => 'status-sukses',
        'Belum Dinilai' => 'status-peringatan',
        'Belum Memenuhi' => 'status-gagal',
    ];
    $statusTugasKelas = [
        'Selesai' => 'status-sukses',
        'Diserahkan' => 'status-info',
        'Ditugaskan' => 'status-netral',
        'Perlu Perbaikan' => 'status-peringatan',
    ];
    $calon = $peserta->calonPeserta();
    $pengajuan = $calon?->pengajuan;
    $institusi = $calon?->institusi_asal ?? $pengajuan?->nama_institusi ?? '—';
    $evaluasi = $peserta->evaluasiPeserta;
@endphp
<div class="judul-aksi">
    <h1>Catat Evaluasi Peserta</h1>
    @if ($evaluasi)
        <span class="status {{ $statusEvaluasiKelas[$evaluasi->status_evaluasi] ?? 'status-netral' }}">{{ $evaluasi->status_evaluasi }}</span>
    @else
        <span class="status status-netral">Belum Dievaluasi</span>
    @endif
</div>

<div class="kartu">
    <h2>Informasi peserta</h2>
    <table>
        <tr><th style="width:200px">Peserta</th><td>{{ $peserta->pengguna->nama_lengkap }}</td></tr>
        <tr><th>Nomor Induk</th><td>{{ $calon?->nomor_induk_pendidikan ?? '—' }}</td></tr>
        <tr><th>Institusi Asal</th><td>{{ $institusi }}</td></tr>
        <tr><th>Jalur Magang</th><td>{{ $jalur === 'DTI' ? 'Rekrutmen DTI' : 'Institusi' }}</td></tr>
        <tr><th>Periode Magang</th><td>{{ $peserta->tanggal_mulai->translatedFormat('d M Y') }} – {{ $peserta->tanggal_selesai->translatedFormat('d M Y') }}</td></tr>
        @if ($evaluasi)
            <tr><th>Waktu Evaluasi Terakhir</th><td>{{ $evaluasi->waktu_evaluasi?->translatedFormat('d M Y, H:i') ?? '—' }}</td></tr>
            @if ($jalur === 'INSTITUSI' && $evaluasi->path_dokumen_evaluasi)
                <tr>
                    <th>Dokumen Evaluasi Saat Ini</th>
                    <td><a href="{{ asset('storage/'.$evaluasi->path_dokumen_evaluasi) }}" target="_blank" rel="noopener">Lihat Dokumen Evaluasi</a></td>
                </tr>
            @endif
        @endif
    </table>
</div>

<div class="kartu">
    <h2>Ringkasan tugas peserta</h2>
    <table>
        <thead>
            <tr>
                <th>Judul Tugas</th>
                <th>Tenggat</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($peserta->tugas as $t)
            <tr>
                <td>{{ $t->judul }}</td>
                <td class="redup">{{ $t->tenggat?->translatedFormat('d M Y') ?? '—' }}</td>
                <td><span class="status {{ $statusTugasKelas[$t->status] ?? 'status-netral' }}">{{ $t->status }}</span></td>
            </tr>
        @empty
            <tr><td colspan="3" class="redup">Belum ada tugas yang diberikan kepada peserta ini.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="kartu form">
    <h2>Formulir evaluasi</h2>
    <form method="POST" action="{{ route('mentor.evaluasi.update', $peserta) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @if ($jalur === 'DTI')
            <label>Status pemenuhan tugas
                <select name="status_evaluasi" required>
                    @foreach (\App\Models\EvaluasiPeserta::STATUS_DTI as $opsi)
                        <option value="{{ $opsi }}" @selected(old('status_evaluasi', $evaluasi?->status_evaluasi) === $opsi)>{{ $opsi }}</option>
                    @endforeach
                </select>
            </label>
            <label>Catatan pemenuhan tugas <span class="redup">(wajib apabila Belum Memenuhi)</span>
                <textarea name="catatan" rows="4" placeholder="Ringkasan pencapaian tugas atau hal yang belum memenuhi...">{{ old('catatan', $evaluasi?->catatan) }}</textarea>
            </label>
        @else
            <label>Status penilaian institusi
                <select name="status_evaluasi" required>
                    @foreach (\App\Models\EvaluasiPeserta::STATUS_INSTITUSI as $opsi)
                        <option value="{{ $opsi }}" @selected(old('status_evaluasi', $evaluasi?->status_evaluasi) === $opsi)>{{ $opsi }}</option>
                    @endforeach
                </select>
            </label>
            <label>Dokumen hasil evaluasi <span class="redup">({{ $evaluasi?->path_dokumen_evaluasi ? 'opsional — unggah untuk mengganti berkas lama' : 'wajib apabila Sudah Dinilai' }}, PDF/DOC/DOCX/JPG/PNG maks. 5 MB)</span>
                <input type="file" name="dokumen_evaluasi" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            </label>
            <label>Catatan <span class="redup">(opsional)</span>
                <textarea name="catatan" rows="4" placeholder="Catatan tambahan penilaian institusi...">{{ old('catatan', $evaluasi?->catatan) }}</textarea>
            </label>
        @endif

        <div class="aksi">
            <a href="{{ route('mentor.evaluasi.index') }}">Batal</a>
            <button class="btn">Simpan Evaluasi</button>
        </div>
    </form>
</div>
@endsection
