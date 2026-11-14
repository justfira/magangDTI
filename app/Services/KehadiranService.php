<?php

namespace App\Services;

use App\Models\PesertaMagang;
use Carbon\CarbonInterface;

/**
 * Aturan bisnis kehadiran yang dipakai bersama oleh Modul D
 * (UC-08 presensi reguler, UC-09 presensi susulan, UC-10 rekap, UC-11 izin).
 */
class KehadiranService
{
    /**
     * Syarat dasar peserta boleh presensi / mengajukan kehadiran pada suatu tanggal.
     * Mengembalikan pesan penolakan, atau null jika boleh.
     */
    public function alasanTidakBoleh(?PesertaMagang $peserta, CarbonInterface $tanggal): ?string
    {
        if (! $peserta) {
            return 'Anda belum terdaftar sebagai peserta magang.';
        }
        if ($peserta->status_magang !== 'Aktif') {
            return 'Status magang Anda bukan Aktif, presensi tidak dapat dilakukan.';          // UC-08 E3
        }
        if ($tanggal->lt($peserta->tanggal_mulai) || $tanggal->gt($peserta->tanggal_selesai)) {
            return 'Tanggal tersebut berada di luar periode magang Anda.';                     // UC-11 E1
        }
        if (! $peserta->id_seksi || ! $peserta->id_mentor) {
            return 'Penempatan seksi dan mentor Anda belum ditetapkan.';                       // prasyarat UC-08
        }

        return null;
    }

    /** Sudah ada catatan presensi (reguler/susulan/koreksi) pada tanggal tersebut. */
    public function sudahTercatat(PesertaMagang $peserta, CarbonInterface $tanggal): bool
    {
        return $peserta->presensi()->whereDate('tanggal', $tanggal->toDateString())->exists();
    }
}
