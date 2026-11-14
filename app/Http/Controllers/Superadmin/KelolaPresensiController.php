<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\KoreksiPresensi;
use App\Models\Presensi;
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

    /**
     * Rekap Kehadiran Bulanan — GET /superadmin/presensi/rekap
     * Langkah:
     *  - Hitung hari hadir per peserta per bulan: presensi REGULER dengan check-in & check-out + SUSULAN/KOREKSI (satu tanggal = satu hari).
     *  - Izin tidak dihitung sebagai hadir (E3 jika data kosong).
     */
    public function rekap(Request $request): View
    {
        return $this->halamanRancangan(
            'Rekap Kehadiran Bulanan', 'UC-10', 'Superadmin',
            ['Hitung hari hadir per peserta per bulan: presensi REGULER dengan check-in & check-out + SUSULAN/KOREKSI (satu tanggal = satu hari).', 'Izin tidak dihitung sebagai hadir (E3 jika data kosong).'],
            ['presensi', 'koreksi_presensi'],
        );
    }

    /**
     * Ekspor CSV — GET /superadmin/presensi/rekap/csv
     * Langkah:
     *  - return response()->streamDownload(...) berisi hasil rekap (E4).
     */
    public function eksporCsv(Request $request): View
    {
        return $this->halamanRancangan(
            'Ekspor CSV', 'UC-10', 'Superadmin',
            ['return response()->streamDownload(...) berisi hasil rekap (E4).'],
            ['presensi', 'koreksi_presensi'],
        );
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

    private function tanggalDari(?string $nilai): ?Carbon
    {
        try {
            return $nilai ? Carbon::createFromFormat('Y-m-d', $nilai)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
