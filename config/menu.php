<?php

/** Menu samping per peran (mengikuti mockup A3 bagian 3.4). CALON = peserta yang belum diterima. */
return [
    'SUPERADMIN' => [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['label' => 'Program Magang', 'route' => 'superadmin.program-magang.index'],
        ['label' => 'Permohonan Institusi', 'route' => 'superadmin.permohonan.index'],
        ['label' => 'Verifikasi Pengajuan', 'route' => 'superadmin.verifikasi.index'],
        ['label' => 'Manajemen Akun', 'route' => 'superadmin.akun.index'],
        ['label' => 'Aktivasi Peserta', 'route' => 'superadmin.akun.diterima'],
        ['label' => 'Kelola Presensi', 'route' => 'superadmin.presensi.index'],
        ['label' => 'Sertifikat', 'route' => 'superadmin.sertifikat.index'],
        ['label' => 'Monitoring', 'route' => 'monitoring'],
    ],
    'KEPALA_SEKSI' => [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['label' => 'Rekomendasi Penerimaan', 'route' => 'kasi.rekomendasi.index'],
        ['label' => 'Penetapan Seksi & Mentor', 'route' => 'kasi.penempatan.index'],
        ['label' => 'Penyelesaian Magang', 'route' => 'kasi.penyelesaian.index'],
        ['label' => 'Monitoring', 'route' => 'monitoring'],
    ],
    'DIREKTUR' => [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['label' => 'Persetujuan Penerimaan', 'route' => 'direktur.penerimaan.index'],
        ['label' => 'Persetujuan Penyelesaian', 'route' => 'direktur.penyelesaian.index'],
        ['label' => 'Monitoring', 'route' => 'monitoring'],
    ],
    'MENTOR' => [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['label' => 'Peserta Bimbingan', 'route' => 'mentor.bimbingan.index'],
        ['label' => 'Tugas', 'route' => 'mentor.tugas.index'],
        ['label' => 'Verifikasi Izin & Susulan', 'route' => 'mentor.kehadiran.index'],
        ['label' => 'Periksa Logbook', 'route' => 'mentor.logbook.index'],
        ['label' => 'Evaluasi', 'route' => 'mentor.evaluasi.index'],
    ],
    'PESERTA' => [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['label' => 'Penempatan', 'route' => 'peserta.penempatan'],
        ['label' => 'Tugas', 'route' => 'peserta.tugas.index'],
        ['label' => 'Presensi', 'route' => 'peserta.presensi.index'],
        ['label' => 'Izin & Presensi Susulan', 'route' => 'peserta.kehadiran.index'],
        ['label' => 'Logbook', 'route' => 'peserta.logbook.index'],
        ['label' => 'Sertifikat', 'route' => 'peserta.sertifikat.index'],
    ],
    'CALON' => [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['label' => 'Status Pendaftaran', 'route' => 'peserta.pendaftaran.index'],
        ['label' => 'Program Magang', 'route' => 'informasi-program'],
    ],
];
