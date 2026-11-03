<?php

namespace App\Services;

/**
 * Validasi lokasi presensi (UC-08, UC-09). Titik, radius, dan batas akurasi kantor DTI
 * diambil dari config/presensi.php (.env), bukan dari tabel — kantor DTI hanya satu.
 */
class GeofenceService
{
    /** Jarak dua koordinat dalam meter (rumus haversine). */
    public function jarakMeter(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }

    /** @return array{valid: bool, jarak: float, pesan: string} */
    public function periksa(float $lat, float $lon, float $akurasi): array
    {
        $kantor = config('presensi.kantor');
        $jarak = $this->jarakMeter($lat, $lon, (float) $kantor['latitude'], (float) $kantor['longitude']);

        if ($akurasi > (float) $kantor['batas_akurasi_meter']) {
            return ['valid' => false, 'jarak' => $jarak, 'pesan' => 'Akurasi lokasi terlalu rendah, coba lagi di area terbuka.'];
        }
        if ($jarak > (float) $kantor['radius_meter']) {
            return ['valid' => false, 'jarak' => $jarak, 'pesan' => 'Lokasi Anda berada di luar radius kantor DTI.'];
        }

        return ['valid' => true, 'jarak' => $jarak, 'pesan' => 'Lokasi valid.'];
    }
}
