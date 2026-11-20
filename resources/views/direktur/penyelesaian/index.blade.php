@extends('layouts.app')
@section('judul', 'Persetujuan Penyelesaian')
@section('konten')
<div class="kepala-halaman">
    <h1>Persetujuan Penyelesaian</h1>
    <p class="sub-judul">Putuskan rekomendasi penyelesaian magang yang diajukan Kepala Seksi.</p>
</div>

<div class="tab-bar">
    @foreach ($daftarTab as $kunci => $label)
        <a class="tab {{ $tab === $kunci ? 'aktif' : '' }}" href="{{ route('direktur.penyelesaian.index', ['tab' => $kunci]) }}">
            {{ $label }}@if ($kunci === 'menunggu' && $jumlahMenunggu) ({{ $jumlahMenunggu }})@endif
        </a>
    @endforeach
</div>

<div class="kartu">
@if ($penyelesaian->isEmpty())
    <div class="kosong">
        @if ($tab === 'menunggu')
            <b>Tidak ada rekomendasi yang menunggu</b>
            Semua rekomendasi penyelesaian sudah diputuskan.
        @else
            <b>Belum ada riwayat</b>
            Belum ada keputusan penyelesaian yang dibuat.
        @endif
    </div>
@elseif ($tab === 'menunggu')
<table>
    <thead>
        <tr><th>Peserta</th><th>Seksi / Mentor</th><th>Periode</th><th>Rekomendasi Kasi</th><th>Keputusan</th></tr>
    </thead>
    <tbody>
    @foreach ($penyelesaian as $p)
        @php
            $peserta = $p->pesertaMagang;
            $bag = $errors->getBag('keputusan_'.$p->id_penyelesaian);
            $pakaiOld = $bag->any();
        @endphp
        <tr>
            <td><b>{{ $peserta?->pengguna?->nama_lengkap ?? '-' }}</b></td>
            <td>
                {{ $peserta?->seksi?->nama_seksi ?? '-' }}<br>
                <small class="redup">{{ $peserta?->mentor?->pengguna?->nama_lengkap ?? 'Mentor belum ditetapkan' }}</small>
            </td>
            <td>{{ $peserta?->tanggal_mulai?->format('d/m/Y') ?? '-' }} – {{ $peserta?->tanggal_selesai?->format('d/m/Y') ?? '-' }}</td>
            <td>
                <span class="status status-info">{{ $p->rekomendasi }}</span><br>
                <small class="redup">{{ $p->waktu_rekomendasi?->format('d/m/Y H:i') }} · {{ $p->kepalaSeksi?->pengguna?->nama_lengkap ?? '-' }}</small>
                <p>{{ $p->catatan_rekomendasi }}</p>
            </td>
            <td>
                <form class="form" method="POST" action="{{ route('direktur.penyelesaian.update', $p) }}">
                    @csrf
                    @method('PUT')
                    @if ($pakaiOld)
                        <div class="alert alert-gagal"><ul>@foreach ($bag->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                    @endif
                    <label>Keputusan
                        <select name="keputusan" required class="{{ $bag->has('keputusan') ? 'salah' : '' }}">
                            <option value="">— Pilih —</option>
                            @foreach (\App\Models\PenyelesaianMagang::KEPUTUSAN as $k)
                                <option value="{{ $k }}" @selected($pakaiOld && old('keputusan') === $k)>{{ $k }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Catatan <small class="redup">(wajib bila Ditolak)</small>
                        <textarea name="catatan_keputusan" rows="2" maxlength="2000" class="{{ $bag->has('catatan_keputusan') ? 'salah' : '' }}">{{ $pakaiOld ? old('catatan_keputusan') : '' }}</textarea>
                    </label>
                    <div class="aksi"><button class="btn btn-kecil">Simpan Keputusan</button></div>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $penyelesaian->links() }}
@else
<table>
    <thead>
        <tr><th>Peserta</th><th>Seksi</th><th>Rekomendasi Kasi</th><th>Keputusan</th><th>Catatan</th><th>Waktu</th><th>Sertifikat</th></tr>
    </thead>
    <tbody>
    @foreach ($penyelesaian as $p)
        <tr>
            <td><b>{{ $p->pesertaMagang?->pengguna?->nama_lengkap ?? '-' }}</b></td>
            <td>{{ $p->pesertaMagang?->seksi?->nama_seksi ?? '-' }}</td>
            <td>
                {{ $p->rekomendasi }}<br>
                <small class="redup">{{ $p->kepalaSeksi?->pengguna?->nama_lengkap ?? '-' }}</small>
            </td>
            <td><span class="status {{ $p->keputusan === 'Disetujui' ? 'status-sukses' : 'status-gagal' }}">{{ $p->keputusan }}</span></td>
            <td>{{ $p->catatan_keputusan ?: '-' }}</td>
            <td>
                {{ $p->waktu_keputusan?->format('d/m/Y H:i') ?? '-' }}<br>
                <small class="redup">{{ $p->direktur?->pengguna?->nama_lengkap ?? '-' }}</small>
            </td>
            <td>
                @if ($p->keputusan !== 'Disetujui')
                    <span class="redup">-</span>
                @elseif ($p->path_sertifikat)
                    <span class="status status-sukses">Sudah diunggah</span>
                @else
                    <span class="status status-peringatan">Belum diunggah</span>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $penyelesaian->links() }}
@endif
</div>
@endsection
