<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\KoreksiPresensi;
use App\Models\Presensi;
use App\Services\KehadiranService;
use App\Support\Notifikasi;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kelola Presensi (UC-10)
 * Aktor  : Superadmin
 * Tabel  : presensi, koreksi_presensi
 */
class KelolaPresensiController extends Controller
{
    /** Daftar presensi peserta, filter tanggal & nama. */
    public function index(Request $request): View
    {
        $tanggal = $this->tanggalDari($request->query('tanggal')) ?? today();
        $cari = trim((string) $request->query('cari'));

        $presensi = Presensi::with('pesertaMagang.pengguna')
            ->withCount('koreksiPresensi')
            ->whereDate('tanggal', $tanggal)
            ->when($cari !== '', fn ($q) => $q->whereHas('pesertaMagang.pengguna', fn ($u) => $u->where('nama_lengkap', 'like', "%{$cari}%")))
            ->orderBy('waktu_checkin')
            ->paginate(20)->withQueryString();

        return view('superadmin.presensi.index', compact('presensi', 'tanggal', 'cari'));
    }

    /** Rekap kehadiran bulanan (UC-10 langkah 3–11; E3 jika tidak ada data). */
    public function rekap(Request $request, KehadiranService $kehadiran): View
    {
        $bulan = $this->bulanDari($request->query('bulan'));
        $rekap = $kehadiran->rekapBulanan($bulan->year, $bulan->month);

        return view('superadmin.presensi.rekap', compact('rekap', 'bulan'));
    }

    /** Ekspor rekap yang sama ke CSV (UC-10 langkah 12–14). Pemisah ";" agar langsung terbaca Excel berbahasa Indonesia. */
    public function eksporCsv(Request $request, KehadiranService $kehadiran): StreamedResponse|RedirectResponse
    {
        $bulan = $this->bulanDari($request->query('bulan'));
        $rekap = $kehadiran->rekapBulanan($bulan->year, $bulan->month);
        if ($rekap->isEmpty()) {
            return back()->withErrors(['rekap' => 'Data rekap tidak tersedia untuk periode ini.']);       // E3
        }

        Log::channel('aktivitas')->info('ekspor_rekap_presensi', ['bulan' => $bulan->format('Y-m'), 'oleh' => auth()->id()]);

        return response()->streamDownload(function () use ($rekap, $bulan) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");                                                            // BOM UTF-8 untuk Excel
            fputcsv($out, ['Periode', 'Nama Peserta', 'Seksi', 'Hari Hadir', 'Dari Presensi Susulan', 'Izin Penuh', 'Izin Sebagian', 'Belum Check-out (tidak dihitung)'], ';');
            foreach ($rekap as $r) {
                fputcsv($out, [$bulan->format('Y-m'), $r->pengguna->nama_lengkap, $r->seksi?->nama_seksi ?? '-',
                    $r->hadir, $r->hadir_susulan, $r->izin_penuh, $r->izin_sebagian, $r->belum_checkout], ';');
            }
            fclose($out);
        }, 'rekap-kehadiran-'.$bulan->format('Y-m').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Form koreksi + riwayat koreksi sebelumnya (UC-10 A1). */
    public function koreksi(Request $request, Presensi $presensi): View
    {
        $presensi->load(['pesertaMagang.pengguna', 'koreksiPresensi' => fn ($q) => $q->with('pengoreksi.pengguna')->latest('id_koreksi')]);

        return view('superadmin.presensi.koreksi', compact('presensi'));
    }

    /** Simpan koreksi: alasan & bukti wajib (E1), data sebelum/sesudah disimpan sebagai riwayat. */
    public function simpanKoreksi(Request $request, Presensi $presensi): RedirectResponse
    {
        $data = $request->validate([
            'jam_checkin' => ['required', 'date_format:H:i'],
            'jam_checkout' => ['nullable', 'date_format:H:i', 'after:jam_checkin'],
            'alasan' => ['required', 'string', 'min:10', 'max:2000'],
            'bukti' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ], [
            'alasan.required' => 'Alasan koreksi wajib diisi.',
            'bukti.required' => 'Bukti pendukung koreksi wajib dilampirkan.',
            'jam_checkout.after' => 'Jam check-out harus setelah jam check-in.',                      // E2
        ]);

        $tanggal = $presensi->tanggal->toDateString();
        $sesudah = [
            'sumber' => 'KOREKSI',
            'waktu_checkin' => Carbon::parse("$tanggal {$data['jam_checkin']}"),
            'waktu_checkout' => $data['jam_checkout'] ? Carbon::parse("$tanggal {$data['jam_checkout']}") : null,
        ];
        $sebelum = $this->ringkas($presensi);
        if ($sebelum === $this->ringkas((clone $presensi)->fill($sesudah))) {
            return back()->withInput()->withErrors(['jam_checkin' => 'Tidak ada data yang berubah.']);
        }

        $pathBukti = $request->file('bukti')->store('koreksi-presensi', 'local');
        try {
            DB::transaction(function () use ($presensi, $sesudah, $sebelum, $data, $pathBukti) {
                $presensi->update($sesudah);
                KoreksiPresensi::create([
                    'id_presensi' => $presensi->id_presensi,
                    'id_pengoreksi' => $this->pegawaiLogin()->id_pegawai,
                    'alasan' => $data['alasan'],
                    'path_bukti' => $pathBukti,
                    'data_sebelum' => $sebelum,
                    'data_sesudah' => $this->ringkas($presensi->fresh()),
                    'waktu_koreksi' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($pathBukti);
            throw $e;
        }

        Notifikasi::kirim($presensi->pesertaMagang->id_pengguna, 'KOREKSI_PRESENSI',
            'Presensi tanggal '.$presensi->tanggal->format('d/m/Y').' dikoreksi oleh Superadmin.', 'presensi', $presensi->id_presensi);
        Log::channel('aktivitas')->info('koreksi_presensi', ['id_presensi' => $presensi->id_presensi, 'oleh' => auth()->id()]);

        return redirect()->route('superadmin.presensi.koreksi', $presensi)->with('sukses', 'Koreksi presensi berhasil disimpan.');
    }

    /** Unduh bukti pendukung koreksi (disimpan privat). */
    public function bukti(KoreksiPresensi $koreksi): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($koreksi->path_bukti), 404, 'Berkas bukti tidak ditemukan.');

        return Storage::disk('local')->response($koreksi->path_bukti);
    }

    /** Bentuk data presensi yang dicatat sebagai riwayat sebelum/sesudah. */
    private function ringkas(Presensi $p): array
    {
        return [
            'tanggal' => $p->tanggal?->toDateString(),
            'sumber' => $p->sumber,
            'waktu_checkin' => $p->waktu_checkin?->format('Y-m-d H:i:s'),
            'waktu_checkout' => $p->waktu_checkout?->format('Y-m-d H:i:s'),
        ];
    }

    /** Parameter ?bulan=YYYY-MM (default bulan ini). */
    private function bulanDari(?string $nilai): Carbon
    {
        try {
            return $nilai ? Carbon::createFromFormat('!Y-m', $nilai)->startOfMonth() : today()->startOfMonth();
        } catch (\Throwable) {
            return today()->startOfMonth();
        }
    }

    private function tanggalDari(?string $nilai): ?Carbon
    {
        try {
            return $nilai ? Carbon::createFromFormat('Y-m-d', $nilai)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
