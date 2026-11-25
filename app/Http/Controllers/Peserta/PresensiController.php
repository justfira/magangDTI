<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Services\KehadiranService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Presensi Reguler (UC-08)
 * Aktor  : Peserta Magang
 * Tabel  : presensi
 *
 * Satu baris presensi per peserta per tanggal: check-in membuat baris, check-out mengisi baris yang sama.
 * Waktu selalu memakai waktu server (now()), bukan waktu dari perangkat peserta.
 */
class PresensiController extends Controller
{
    public function __construct(private KehadiranService $kehadiran)
    {
    }

    /** Presensi Harian — status hari ini & riwayat bulan berjalan. */
    public function index(Request $request): View
    {
        $peserta = $this->pesertaLogin();
        abort_unless($peserta, 403, 'Anda belum terdaftar sebagai peserta magang.');

        $hariIni = $peserta->presensi()->whereDate('tanggal', today())->first();
        $riwayat = $peserta->presensi()
            ->whereYear('tanggal', today()->year)->whereMonth('tanggal', today()->month)
            ->orderByDesc('tanggal')->get();

        return view('peserta.presensi.index', [
            'peserta' => $peserta,
            'hariIni' => $hariIni,
            'riwayat' => $riwayat,
            'alasanTidakBoleh' => $this->kehadiran->alasanTidakBoleh($peserta, today())
                ?? ($this->kehadiran->izinPenuhDisetujui($peserta, today()) ? 'Anda memiliki izin penuh yang disetujui untuk hari ini.' : null),
        ]);
    }

    /** Check-in (UC-08 langkah 3–10). */
    public function checkIn(Request $request): RedirectResponse
    {
        $peserta = $this->pesertaLogin();
        if ($alasan = $this->kehadiran->alasanTidakBoleh($peserta, today())) {
            return back()->withErrors(['presensi' => $alasan]);                       // E3
        }
        if ($this->kehadiran->sudahTercatat($peserta, today())) {
            return back()->with('info', 'Anda sudah check-in hari ini.');               // A1
        }
        if ($this->kehadiran->izinPenuhDisetujui($peserta, today())) {
            return back()->withErrors(['presensi' => 'Anda memiliki izin penuh yang disetujui untuk hari ini.']);
        }
        [$lokasi, $salahLokasi] = $this->kehadiran->periksaLokasi($request->only('latitude', 'longitude', 'akurasi'));
        if ($salahLokasi) {
            return back()->withErrors(['presensi' => $salahLokasi]);                  // E1 / E2
        }

        try {
            $presensi = Presensi::create([
                'id_peserta_magang' => $peserta->id_peserta_magang,
                'tanggal' => today()->toDateString(),
                'sumber' => 'REGULER',
                'waktu_checkin' => now(),                                             // waktu server
                'lat_checkin' => $lokasi['latitude'],
                'long_checkin' => $lokasi['longitude'],
                'akurasi_checkin' => $lokasi['akurasi'],
            ]);
        } catch (UniqueConstraintViolationException) {
            // dua klik bersamaan: baris kedua ditolak UNIQUE (id_peserta_magang, tanggal)
            return back()->with('info', 'Anda sudah check-in hari ini.');
        }

        Log::channel('aktivitas')->info('check_in', ['id_presensi' => $presensi->id_presensi, 'id_peserta_magang' => $peserta->id_peserta_magang]);

        return back()->with('sukses', 'Check-in berhasil pukul '.$presensi->waktu_checkin->format('H:i').'.');
    }

    /** Check-out (UC-08 langkah 11–14) — mengisi baris presensi yang sama. */
    public function checkOut(Request $request): RedirectResponse
    {
        $peserta = $this->pesertaLogin();
        if ($alasan = $this->kehadiran->alasanTidakBoleh($peserta, today())) {
            return back()->withErrors(['presensi' => $alasan]);
        }

        $presensi = $peserta->presensi()->whereDate('tanggal', today())->first();
        if (! $presensi || ! $presensi->waktu_checkin) {
            return back()->withErrors(['presensi' => 'Anda belum check-in hari ini, check-out tidak dapat dilakukan.']);   // E4
        }
        if ($presensi->sumber !== 'REGULER') {
            return back()->withErrors(['presensi' => 'Kehadiran hari ini tercatat dari presensi susulan/koreksi, tidak perlu check-out.']);
        }
        if ($presensi->waktu_checkout) {
            return back()->with('info', 'Anda sudah check-out hari ini.');
        }
        [$lokasi, $salahLokasi] = $this->kehadiran->periksaLokasi($request->only('latitude', 'longitude', 'akurasi'));
        if ($salahLokasi) {
            return back()->withErrors(['presensi' => $salahLokasi]);                  // UC-08 langkah 12
        }

        $presensi->update([
            'waktu_checkout' => now(),
            'lat_checkout' => $lokasi['latitude'],
            'long_checkout' => $lokasi['longitude'],
            'akurasi_checkout' => $lokasi['akurasi'],
        ]);
        Log::channel('aktivitas')->info('check_out', ['id_presensi' => $presensi->id_presensi]);

        return back()->with('sukses', 'Check-out berhasil pukul '.$presensi->waktu_checkout->format('H:i').'.');
    }
}
