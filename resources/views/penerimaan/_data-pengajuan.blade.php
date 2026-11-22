{{--
    Data pendaftar / institusi untuk satu pengajuan.
    Variabel: $pengajuan, opsional $calon (CalonPeserta yang sedang dibahas — untuk jalur institusi)
--}}
@php $c = $calon ?? $pengajuan->calonPeserta->first(); @endphp
<dl class="pn-dl">
    <dt>Kode</dt><dd>{{ $pengajuan->kode_pengajuan }}</dd>
    <dt>Jalur</dt><dd>{{ \App\Services\AlurPenerimaanService::labelJalur($pengajuan->jalur) }}</dd>
    @if ($pengajuan->jalur === 'INSTITUSI')
        <dt>Institusi</dt><dd>{{ $pengajuan->nama_institusi }}</dd>
        <dt>Nomor surat</dt><dd>{{ $pengajuan->nomor_surat }} · {{ $pengajuan->tanggal_surat?->translatedFormat('d M Y') }}</dd>
        <dt>Kontak</dt><dd>{{ $pengajuan->kontak_institusi ?: '—' }}</dd>
        @isset($calon)
            <dt>Nama calon</dt><dd>{{ $c->nama_lengkap }}</dd>
            <dt>NIM/NIS</dt><dd>{{ $c->nomor_induk_pendidikan ?? '—' }}</dd>
            <dt>Jurusan/Prodi</dt><dd>{{ $c->jurusan_prodi ?? '—' }}</dd>
        @endisset
    @else
        <dt>Nama lengkap</dt><dd>{{ $c?->nama_lengkap ?? $pengajuan->diajukanOleh?->nama_lengkap ?? '—' }}</dd>
        <dt>Email</dt><dd>{{ $c?->email ?? $pengajuan->diajukanOleh?->email ?? '—' }}</dd>
        <dt>No. HP</dt><dd>{{ $c?->no_hp ?? '—' }}</dd>
        <dt>NIM/NIS</dt><dd>{{ $c?->nomor_induk_pendidikan ?? '—' }}</dd>
        <dt>Asal kampus</dt><dd>{{ $c?->institusi_asal ?? '—' }}</dd>
        <dt>Jurusan/Prodi</dt><dd>{{ $c?->jurusan_prodi ?? '—' }}</dd>
        <dt>Program</dt><dd>{{ $pengajuan->program?->nama_program ?? '—' }}</dd>
    @endif
    <dt>Tanggal diajukan</dt><dd>{{ $pengajuan->tanggal_pengajuan?->translatedFormat('d F Y, H:i') }}</dd>
</dl>
