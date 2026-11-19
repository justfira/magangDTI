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

<div class="kepala-halaman">
    <div>
        <a class="tautan-kembali" href="{{ route('mentor.evaluasi.index') }}"><x-ikon nama="kembali" /> Kembali ke daftar evaluasi</a>
        <h1>Catat Evaluasi Peserta</h1>
        <p class="sub-judul">Tinjau pemenuhan tugas peserta dan simpan hasil evaluasi sesuai jalur magangnya.</p>
    </div>
    <div class="kepala-aksi">
        @if ($evaluasi)
            <span class="status {{ $statusEvaluasiKelas[$evaluasi->status_evaluasi] ?? 'status-netral' }}">{{ $evaluasi->status_evaluasi }}</span>
        @else
            <span class="status status-netral">Belum Dievaluasi</span>
        @endif
    </div>
</div>

<div class="kartu">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat"><x-ikon nama="pengguna" /></span>
            <div>
                <h2>Informasi peserta</h2>
                <p>Identitas, asal institusi, dan status evaluasi terakhir peserta.</p>
            </div>
        </div>
        @if ($jalur === 'INSTITUSI' && $evaluasi?->path_dokumen_evaluasi)
            <a class="btn btn-garis btn-kecil" href="{{ asset('storage/'.$evaluasi->path_dokumen_evaluasi) }}" target="_blank" rel="noopener"><x-ikon nama="berkas" /> Lihat Dokumen Evaluasi</a>
        @endif
    </div>
    <table>
        <tr><th style="width:210px">Peserta</th><td><b>{{ $peserta->pengguna->nama_lengkap }}</b></td></tr>
        <tr><th>Nomor Induk</th><td>{{ $calon?->nomor_induk_pendidikan ?? '—' }}</td></tr>
        <tr><th>Institusi Asal</th><td>{{ $institusi }}</td></tr>
        <tr>
            <th>Jalur Magang</th>
            <td><span class="chip {{ $jalur === 'DTI' ? 'chip-hijau' : '' }}">{{ $jalur === 'DTI' ? 'Rekrutmen DTI' : 'Institusi' }}</span></td>
        </tr>
        <tr>
            <th>Periode Magang</th>
            <td><span class="sel-ikon"><x-ikon nama="kalender" /> {{ $peserta->tanggal_mulai->translatedFormat('d M Y') }} – {{ $peserta->tanggal_selesai->translatedFormat('d M Y') }}</span></td>
        </tr>
        @if ($evaluasi)
            <tr>
                <th>Waktu Evaluasi Terakhir</th>
                <td><span class="sel-ikon"><x-ikon nama="jam" /> {{ $evaluasi->waktu_evaluasi?->translatedFormat('d M Y, H:i') ?? '—' }}</span></td>
            </tr>
        @endif
    </table>
</div>

<div class="kartu kartu-tabel">
    <div class="kartu-kepala">
        <div class="kartu-judul">
            <span class="ikon-bulat kuning"><x-ikon nama="tugas" /></span>
            <div>
                <h2>Ringkasan tugas peserta</h2>
                <p>Acuan pemenuhan tugas selama periode pembimbingan di DTI.</p>
            </div>
        </div>
    </div>
    <div class="tabel-wadah">
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
                    <td><b>{{ $t->judul }}</b></td>
                    <td>
                        @if ($t->tenggat)
                            <span class="sel-ikon"><x-ikon nama="kalender" /> {{ $t->tenggat->translatedFormat('d M Y') }}</span>
                        @else
                            <span class="redup">—</span>
                        @endif
                    </td>
                    <td><span class="status {{ $statusTugasKelas[$t->status] ?? 'status-netral' }}">{{ $t->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="3">
                    <div class="kosong">
                        <div class="ilustrasi"><x-ikon nama="tugas" /></div>
                        <b>Belum ada tugas yang diberikan.</b>
                        Belum terdapat catatan tugas untuk peserta ini.
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<form class="kartu form form-kartu" method="POST" action="{{ route('mentor.evaluasi.update', $peserta) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="form-bagian">
        <div class="form-bagian-ket">
            <h2><span class="ikon-bulat"><x-ikon nama="medali" /></span> Hasil evaluasi</h2>
            <p>
                @if ($jalur === 'DTI')
                    Penilaian jalur Rekrutmen DTI didasarkan pada status pemenuhan tugas dan catatan mentor.
                @else
                    Penilaian jalur Institusi menggunakan format dokumen evaluasi dari sekolah/perguruan tinggi pengirim.
                @endif
            </p>
        </div>
        <div class="form-bagian-isi">
            <div class="info-kotak">
                <x-ikon nama="info" />
                <p>
                    @if ($jalur === 'DTI')
                        Catatan pemenuhan tugas <b>wajib diisi</b> apabila status dipilih sebagai <b>Belum Memenuhi</b>.
                    @else
                        Dokumen hasil evaluasi (PDF/DOC/DOCX/JPG/PNG, maks. 5 MB) <b>wajib diunggah</b> saat menetapkan status <b>Sudah Dinilai</b> apabila belum ada berkas tersimpan.
                    @endif
                </p>
            </div>

            @if ($jalur === 'DTI')
                <label>Status pemenuhan tugas
                    <select name="status_evaluasi" required>
                        @foreach (\App\Models\EvaluasiPeserta::STATUS_DTI as $opsi)
                            <option value="{{ $opsi }}" @selected(old('status_evaluasi', $evaluasi?->status_evaluasi) === $opsi)>{{ $opsi }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Catatan pemenuhan tugas <small class="redup">(wajib apabila Belum Memenuhi)</small>
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
                <label>Dokumen hasil evaluasi
                    <input type="file" name="dokumen_evaluasi" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <small class="redup">
                        {{ $evaluasi?->path_dokumen_evaluasi ? 'Opsional — unggah berkas baru untuk mengganti dokumen lama.' : 'Wajib apabila status Sudah Dinilai.' }} Format yang diterima: PDF, DOC, DOCX, JPG, JPEG, atau PNG (maks. 5 MB).
                    </small>
                </label>
                <label>Catatan <small class="redup">(opsional)</small>
                    <textarea name="catatan" rows="4" placeholder="Catatan tambahan penilaian institusi...">{{ old('catatan', $evaluasi?->catatan) }}</textarea>
                </label>
            @endif
        </div>
    </div>

    <div class="form-kaki">
        <a class="btn btn-halus" href="{{ route('mentor.evaluasi.index') }}">Batal</a>
        <button class="btn"><x-ikon nama="centang-saja" /> Simpan Evaluasi</button>
    </div>
</form>
@endsection
