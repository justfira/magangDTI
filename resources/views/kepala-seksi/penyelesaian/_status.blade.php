{{-- Label status satu penyelesaian_magang. Variabel: $penyelesaian (boleh null = belum direkomendasikan). --}}
@php
    [$kelas, $teks] = match (true) {
        $penyelesaian === null => ['status-netral', 'Belum direkomendasikan'],
        $penyelesaian->keputusan === 'Disetujui' => ['status-sukses', 'Disetujui Direktur'],
        $penyelesaian->keputusan === 'Ditolak' => ['status-gagal', 'Ditolak Direktur'],
        $penyelesaian->rekomendasi === 'Perlu Tindak Lanjut' => ['status-peringatan', 'Perlu Tindak Lanjut'],
        default => ['status-info', 'Menunggu keputusan Direktur'],
    };
@endphp
<span class="status {{ $kelas }}">{{ $teks }}</span>
