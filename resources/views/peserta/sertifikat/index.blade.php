@extends('layouts.app')
@section('judul', 'Sertifikat Magang')
@section('konten')
<div class="kepala-halaman">
    <h1>Sertifikat Magang</h1>
    <p class="sub-judul">Pantau proses penyelesaian magang dan unduh sertifikat Anda.</p>
</div>

@forelse ($daftar as $item)
    @php
        $peserta = $item['peserta'];
        $p = $item['penyelesaian'];
        $layak = $p?->rekomendasi === 'Layak Diselesaikan';
        $disetujui = $p?->keputusan === 'Disetujui';
        $adaSertifikat = $disetujui && filled($p->path_sertifikat);
    @endphp
    <div class="kartu">
        <div class="kartu-kepala">
            <h2>Magang {{ $peserta->seksi?->nama_seksi ? 'di '.$peserta->seksi->nama_seksi : '' }}</h2>
            <span class="status {{ $peserta->status_magang === 'Selesai' ? 'status-sukses' : 'status-info' }}">{{ $peserta->status_magang }}</span>
        </div>
        <p class="redup">Periode: {{ $peserta->tanggal_mulai?->format('d/m/Y') ?? '-' }} – {{ $peserta->tanggal_selesai?->format('d/m/Y') ?? '-' }}</p>

        <ul class="langkah">
            {{-- 1. Rekomendasi Kepala Seksi --}}
            @if ($p === null)
                <li class="aktif"><b>Rekomendasi Kepala Seksi</b><small>Menunggu peninjauan oleh Kepala Seksi.</small></li>
            @else
                <li class="{{ $layak ? 'selesai' : 'aktif' }}">
                    <b>Rekomendasi Kepala Seksi: {{ $p->rekomendasi }}</b>
                    <small>{{ $p->waktu_rekomendasi?->format('d/m/Y H:i') }}</small>
                    @if ($p->catatan_rekomendasi)<p>{{ $p->catatan_rekomendasi }}</p>@endif
                </li>
            @endif

            {{-- 2. Keputusan Direktur --}}
            @if (! $layak)
                <li><b>Keputusan Direktur</b><small>Menunggu rekomendasi kelayakan dari Kepala Seksi.</small></li>
            @elseif ($p->keputusan === null)
                <li class="aktif"><b>Keputusan Direktur</b><small>Sedang ditinjau Direktur.</small></li>
            @else
                <li class="{{ $disetujui ? 'selesai' : 'aktif' }}">
                    <b>Keputusan Direktur: {{ $p->keputusan }}</b>
                    <small>{{ $p->waktu_keputusan?->format('d/m/Y H:i') }}</small>
                    @if ($p->catatan_keputusan)<p>{{ $p->catatan_keputusan }}</p>@endif
                </li>
            @endif

            {{-- 3. Sertifikat --}}
            @if ($adaSertifikat)
                <li class="selesai">
                    <b>Sertifikat tersedia</b>
                    <small>No. {{ $p->nomor_sertifikat }} · diunggah {{ $p->waktu_unggah?->format('d/m/Y H:i') }}</small>
                </li>
            @elseif ($disetujui)
                <li class="aktif"><b>Sertifikat</b><small>Sertifikat sedang disiapkan oleh Superadmin.</small></li>
            @else
                <li><b>Sertifikat</b><small>Tersedia setelah penyelesaian disetujui Direktur.</small></li>
            @endif
        </ul>

        @if ($adaSertifikat)
            <div class="aksi"><a class="btn" href="{{ route('peserta.sertifikat.unduh', $p) }}">Unduh Sertifikat (PDF)</a></div>
        @else
            <div class="kosong">
                <b>Sertifikat belum tersedia</b>
                {{ $disetujui ? 'Anda akan menerima notifikasi begitu sertifikat diunggah.' : 'Sertifikat diterbitkan setelah penyelesaian magang Anda disetujui Direktur.' }}
            </div>
        @endif
    </div>
@empty
    <div class="kartu">
        <div class="kosong">
            <b>Belum ada data magang</b>
            Anda belum terdaftar sebagai peserta magang, sehingga belum ada sertifikat.
        </div>
    </div>
@endforelse
@endsection
