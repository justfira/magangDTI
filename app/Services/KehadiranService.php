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
    public function __construct(private GeofenceService $geofence)
    {
    }

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

    /**
     * Validasi lokasi dari browser (UC-08 langkah 4–7, UC-09 langkah 6–7).
     *
     * @return array{0: array{latitude: float, longitude: float, akurasi: float}|null, 1: string|null}
     *         [data lokasi, pesan penolakan]
     */
    public function periksaLokasi(array $input): array
    {
        $lat = $input['latitude'] ?? null;
        $lon = $input['longitude'] ?? null;
        $akurasi = $input['akurasi'] ?? null;

        if (! is_numeric($lat) || ! is_numeric($lon) || ! is_numeric($akurasi)) {
            return [null, 'Lokasi diperlukan untuk melakukan presensi. Izinkan akses lokasi di browser Anda.'];   // UC-08 E1
        }
        if (abs((float) $lat) > 90 || abs((float) $lon) > 180 || (float) $akurasi < 0) {
            return [null, 'Data lokasi tidak valid.'];
        }

        $hasil = $this->geofence->periksa((float) $lat, (float) $lon, (float) $akurasi);
        if (! $hasil['valid']) {
            return [null, $hasil['pesan'].' (jarak ± '.round($hasil['jarak']).' m)'];                          // UC-08 E2
        }

        return [['latitude' => (float) $lat, 'longitude' => (float) $lon, 'akurasi' => (float) $akurasi], null];
    }

    /** Sudah ada catatan presensi (reguler/susulan/koreksi) pada tanggal tersebut. */
    public function sudahTercatat(PesertaMagang $peserta, CarbonInterface $tanggal): bool
    {
        return $peserta->presensi()->whereDate('tanggal', $tanggal->toDateString())->exists();
    }
}
