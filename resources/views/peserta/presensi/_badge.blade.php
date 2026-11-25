{{-- Badge status Modul D. Pakai: @include('peserta.presensi._badge', ['status' => 'Disetujui']) --}}
@php
    $kelas = match ($status) {
        'Disetujui', 'Hadir', 'Selesai' => 'pr-sukses',
        'Menunggu Verifikasi', 'Belum check-out', 'Sudah check-in' => 'pr-tunggu',
        'Ditolak' => 'pr-gagal',
        'SUSULAN', 'KOREKSI', 'Izin' => 'pr-info',
        default => 'pr-netral',
    };
@endphp
<span class="pr-badge {{ $kelas }}">{{ $label ?? $status }}</span>
