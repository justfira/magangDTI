@extends('layouts.app')
@section('judul', 'Sertifikat Magang')
@section('konten')
<div class="kepala-halaman">
    <h1>Sertifikat Magang</h1>
    <p class="sub-judul">Unggah sertifikat untuk peserta yang penyelesaian magangnya sudah disetujui Direktur.</p>
</div>

<div class="tab-bar">
    @foreach ($daftarTab as $kunci => $label)
        <a class="tab {{ $tab === $kunci ? 'aktif' : '' }}" href="{{ route('superadmin.sertifikat.index', ['tab' => $kunci]) }}">
            {{ $label }}@if ($kunci === 'belum' && $jumlahBelum) ({{ $jumlahBelum }})@endif
        </a>
    @endforeach
</div>

<div class="kartu">
@if ($penyelesaian->isEmpty())
    <div class="kosong">
        @if ($tab === 'belum')
            <b>Tidak ada sertifikat yang perlu diunggah</b>
            Semua penyelesaian yang disetujui sudah memiliki sertifikat.
        @else
            <b>Belum ada sertifikat</b>
            Sertifikat yang sudah diunggah akan tampil di sini.
        @endif
    </div>
@else
<table>
    <thead>
        <tr>
            <th>Peserta</th><th>Seksi</th><th>Periode</th><th>Disetujui</th>
            @if ($tab === 'sudah')<th>Sertifikat</th>@endif
            <th>{{ $tab === 'sudah' ? 'Unggah Ulang' : 'Unggah Sertifikat' }}</th>
        </tr>
    </thead>
    <tbody>
    @foreach ($penyelesaian as $p)
        @php
            $peserta = $p->pesertaMagang;
            $bag = $errors->getBag('sertifikat_'.$p->id_penyelesaian);
            $pakaiOld = $bag->any();
        @endphp
        <tr>
            <td><b>{{ $peserta?->pengguna?->nama_lengkap ?? '-' }}</b></td>
            <td>{{ $peserta?->seksi?->nama_seksi ?? '-' }}</td>
            <td>{{ $peserta?->tanggal_mulai?->format('d/m/Y') ?? '-' }} – {{ $peserta?->tanggal_selesai?->format('d/m/Y') ?? '-' }}</td>
            <td>{{ $p->waktu_keputusan?->format('d/m/Y') ?? '-' }}</td>
            @if ($tab === 'sudah')
                <td>
                    <b>{{ $p->nomor_sertifikat }}</b><br>
                    <small class="redup">{{ $p->waktu_unggah?->format('d/m/Y H:i') ?? '-' }} · {{ $p->pengunggah?->pengguna?->nama_lengkap ?? '-' }}</small>
                </td>
            @endif
            <td>
                <form class="form" method="POST" action="{{ route('superadmin.sertifikat.unggah', $p) }}" enctype="multipart/form-data">
                    @csrf
                    @if ($pakaiOld)
                        <div class="alert alert-gagal"><ul>@foreach ($bag->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                    @endif
                    <label>Nomor sertifikat
                        <input name="nomor_sertifikat" maxlength="100" required
                               value="{{ $pakaiOld ? old('nomor_sertifikat') : $p->nomor_sertifikat }}"
                               class="{{ $bag->has('nomor_sertifikat') ? 'salah' : '' }}">
                    </label>
                    <label>File PDF <small class="redup">(maks. 5 MB)</small>
                        <input type="file" name="file_sertifikat" accept="application/pdf,.pdf" required
                               class="{{ $bag->has('file_sertifikat') ? 'salah' : '' }}">
                    </label>
                    @if ($tab === 'sudah')
                        <p class="petunjuk">File lama akan diganti dengan file baru.</p>
                    @endif
                    <div class="aksi"><button class="btn btn-kecil">{{ $tab === 'sudah' ? 'Unggah Ulang' : 'Unggah' }}</button></div>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $penyelesaian->links() }}
@endif
</div>
@endsection
