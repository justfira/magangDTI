<?php

namespace App\Services;

use App\Models\PesertaMagang;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

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

    /**
     * Rekap kehadiran bulanan per peserta (UC-10 langkah 5–11).
     *  - hadir      : presensi REGULER lengkap (check-in & check-out) + SUSULAN (disetujui) + KOREKSI.
     *                 Satu tanggal = satu hari karena UNIQUE (id_peserta_magang, tanggal).
     *  - izin       : izin disetujui, TIDAK dihitung sebagai hadir.
     *  - belum_checkout : presensi reguler tanpa check-out, tidak dihitung hadir (perlu dikoreksi).
     */
    public function rekapBulanan(int $tahun, int $bulan): Collection
    {
        $awal = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $akhir = $awal->copy()->endOfMonth();
        $rentang = fn ($q, string $kolom) => $q->whereBetween($kolom, [$awal->toDateString(), $akhir->toDateString()]);

        return PesertaMagang::query()
            ->with(['pengguna', 'seksi'])
            ->whereDate('tanggal_mulai', '<=', $akhir)
            ->whereDate('tanggal_selesai', '>=', $awal)
            ->withCount([
                'presensi as hadir' => fn ($q) => $rentang($q, 'tanggal')->where(fn ($w) => $w
                    ->where(fn ($r) => $r->where('sumber', 'REGULER')->whereNotNull('waktu_checkin')->whereNotNull('waktu_checkout'))
                    ->orWhereIn('sumber', ['SUSULAN', 'KOREKSI'])),
                'presensi as hadir_susulan' => fn ($q) => $rentang($q, 'tanggal')->where('sumber', 'SUSULAN'),
                'presensi as belum_checkout' => fn ($q) => $rentang($q, 'tanggal')->where('sumber', 'REGULER')->whereNull('waktu_checkout'),
                'pengajuanKehadiran as izin_penuh' => fn ($q) => $rentang($q, 'tanggal_kegiatan')
                    ->where('jenis', 'IZIN')->where('jenis_izin', 'Penuh')->where('status', 'Disetujui'),
                'pengajuanKehadiran as izin_sebagian' => fn ($q) => $rentang($q, 'tanggal_kegiatan')
                    ->where('jenis', 'IZIN')->where('jenis_izin', 'Sebagian')->where('status', 'Disetujui'),
            ])
            ->get()
            ->sortBy(fn ($p) => $p->pengguna->nama_lengkap)
            ->values();
    }

    /** Masih ada pengajuan izin/susulan (menunggu atau disetujui) pada tanggal tersebut. */
    public function adaPengajuanAktif(PesertaMagang $peserta, CarbonInterface $tanggal): bool
    {
        return $peserta->pengajuanKehadiran()
            ->whereDate('tanggal_kegiatan', $tanggal->toDateString())
            ->whereIn('status', ['Menunggu Verifikasi', 'Disetujui'])
            ->exists();
    }

    /** Izin Penuh yang sudah disetujui mentor pada tanggal tersebut (UC-11: tidak boleh sekaligus hadir). */
    public function izinPenuhDisetujui(PesertaMagang $peserta, CarbonInterface $tanggal): bool
    {
        return $peserta->pengajuanKehadiran()
            ->where('jenis', 'IZIN')->where('jenis_izin', 'Penuh')->where('status', 'Disetujui')
            ->whereDate('tanggal_kegiatan', $tanggal->toDateString())
            ->exists();
    }

    /** Sudah ada catatan presensi (reguler/susulan/koreksi) pada tanggal tersebut. */
    public function sudahTercatat(PesertaMagang $peserta, CarbonInterface $tanggal): bool
    {
        return $peserta->presensi()->whereDate('tanggal', $tanggal->toDateString())->exists();
    }
}
