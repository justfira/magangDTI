@extends('layouts.app')
@section('judul', 'Tinjau Peserta')
@section('konten')
<div class="judul-aksi">
    <h1>{{ $peserta->pengguna?->nama_lengkap ?? 'Peserta' }}</h1>
    <a class="btn btn-garis" href="{{ route('kasi.penyelesaian.index') }}">← Kembali</a>
</div>

<div class="kartu">
    <div class="kartu-kepala"><h2>Data Peserta</h2></div>
    <div class="dua-kolom">
        <div>
            <p><span class="redup">Institusi</span><br><b>{{ $calon?->institusi_asal ?? '-' }}</b></p>
            <p><span class="redup">Jurusan / Prodi</span><br><b>{{ $calon?->jurusan_prodi ?? '-' }}</b></p>
            <p><span class="redup">Nomor induk</span><br><b>{{ $calon?->nomor_induk_pendidikan ?? '-' }}</b></p>
        </div>
        <div>
            <p><span class="redup">Seksi / Mentor</span><br><b>{{ $peserta->seksi?->nama_seksi ?? '-' }}</b> · {{ $peserta->mentor?->pengguna?->nama_lengkap ?? 'Mentor belum ditetapkan' }}</p>
            <p><span class="redup">Periode magang</span><br><b>{{ $peserta->tanggal_mulai?->format('d/m/Y') ?? '-' }} – {{ $peserta->tanggal_selesai?->format('d/m/Y') ?? '-' }}</b></p>
            <p><span class="redup">Status magang</span><br><span class="status {{ $peserta->status_magang === 'Selesai' ? 'status-sukses' : 'status-info' }}">{{ $peserta->status_magang }}</span></p>
        </div>
    </div>
</div>

<div class="strip">
    <div><div class="l">Tugas selesai</div><div class="n">{{ $peserta->tugas_selesai_count }}/{{ $peserta->tugas_count }}</div></div>
    <div><div class="l">Hari hadir</div><div class="n">{{ $peserta->presensi_count }}</div></div>
    <div><div class="l">Logbook disetujui</div><div class="n">{{ $peserta->logbook_disetujui_count }}/{{ $peserta->logbook_count }}</div></div>
    <div><div class="l">Evaluasi mentor</div><div class="n">{{ $evaluasi?->status_evaluasi ?? 'Belum ada' }}</div></div>
</div>

<div class="kartu">
    <div class="kartu-kepala"><h2>Evaluasi Mentor</h2></div>
    @if ($peringatanEvaluasi)
        <div class="alert alert-peringatan">{{ $peringatanEvaluasi }} Anda tetap dapat membuat rekomendasi.</div>
    @endif
    @if ($evaluasi)
        <p><span class="redup">Waktu evaluasi:</span> {{ $evaluasi->waktu_evaluasi?->format('d/m/Y H:i') ?? '-' }}</p>
        <p><span class="redup">Catatan mentor:</span><br>{{ $evaluasi->catatan ?: '-' }}</p>
    @else
        <p class="redup">Belum ada data evaluasi.</p>
    @endif
</div>

<div class="kartu">
    <div class="kartu-kepala"><h2>Riwayat Penyelesaian</h2></div>
    @forelse ($riwayat as $r)
        <ul class="langkah">
            <li class="selesai">
                <b>Rekomendasi Kepala Seksi: {{ $r->rekomendasi }}</b>
                <small>{{ $r->waktu_rekomendasi?->format('d/m/Y H:i') }} · {{ $r->kepalaSeksi?->pengguna?->nama_lengkap ?? '-' }}</small>
                <p>{{ $r->catatan_rekomendasi }}</p>
            </li>
            @if ($r->rekomendasi === 'Layak Diselesaikan')
                <li class="{{ $r->keputusan ? 'selesai' : 'aktif' }}">
                    @if ($r->keputusan)
                        <b>Keputusan Direktur: {{ $r->keputusan }}</b>
                        <small>{{ $r->waktu_keputusan?->format('d/m/Y H:i') }} · {{ $r->direktur?->pengguna?->nama_lengkap ?? '-' }}</small>
                        @if ($r->catatan_keputusan)<p>{{ $r->catatan_keputusan }}</p>@endif
                    @else
                        <b>Menunggu keputusan Direktur</b>
                    @endif
                </li>
            @endif
        </ul>
        @unless ($loop->last)<hr>@endunless
    @empty
        <p class="redup">Belum ada rekomendasi penyelesaian untuk peserta ini.</p>
    @endforelse
</div>

<div class="kartu">
    <div class="kartu-kepala"><h2>Rekomendasi Penyelesaian</h2></div>
    @if ($alasanTidakBoleh)
        <div class="alert alert-info">{{ $alasanTidakBoleh }}</div>
    @else
        <form class="form" method="POST" action="{{ route('kasi.penyelesaian.store', $peserta) }}">
            @csrf
            <label>Rekomendasi
                <select name="rekomendasi" class="{{ $errors->has('rekomendasi') ? 'salah' : '' }}" required>
                    <option value="">— Pilih —</option>
                    @foreach (\App\Models\PenyelesaianMagang::REKOMENDASI as $r)
                        <option value="{{ $r }}" @selected(old('rekomendasi') === $r)>{{ $r }}</option>
                    @endforeach
                </select>
            </label>
            <p class="petunjuk">"Layak Diselesaikan" dikirim ke Direktur untuk diputuskan. "Perlu Tindak Lanjut" hanya dicatat; Anda dapat membuat rekomendasi baru nanti.</p>
            <label>Catatan rekomendasi
                <textarea name="catatan_rekomendasi" rows="4" maxlength="2000" required class="{{ $errors->has('catatan_rekomendasi') ? 'salah' : '' }}">{{ old('catatan_rekomendasi') }}</textarea>
            </label>
            <div class="aksi"><a href="{{ route('kasi.penyelesaian.index') }}">Batal</a> <button class="btn">Simpan Rekomendasi</button></div>
        </form>
    @endif
</div>
@endsection
