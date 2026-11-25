<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\PengajuanKehadiran;
use App\Models\Presensi;
use App\Services\KehadiranService;
use App\Support\Notifikasi;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Verifikasi Izin & Presensi Susulan (UC-09 / UC-11)
 * Aktor  : Mentor DTI
 * Tabel  : pengajuan_kehadiran, presensi
 *
 * Mentor hanya melihat & memverifikasi pengajuan peserta bimbingannya (peserta_magang.id_mentor).
 */
class VerifikasiKehadiranController extends Controller
{
    public function __construct(private KehadiranService $kehadiran)
    {
    }

    public function index(Request $request): View
    {
        $idMentor = $this->pegawaiLogin()?->id_pegawai;
        $status = in_array($request->query('status'), PengajuanKehadiran::STATUS, true) ? $request->query('status') : null;
        $jenis = in_array($request->query('jenis'), PengajuanKehadiran::JENIS, true) ? $request->query('jenis') : null;

        $query = PengajuanKehadiran::with('pesertaMagang.pengguna')
            ->whereHas('pesertaMagang', fn ($q) => $q->where('id_mentor', $idMentor));
        $jumlah = (clone $query)->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        $pengajuan = $query
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($jenis, fn ($q) => $q->where('jenis', $jenis))
            ->orderByRaw("CASE WHEN status = 'Menunggu Verifikasi' THEN 0 ELSE 1 END")
            ->latest('id_pengajuan_kehadiran')
            ->paginate(15)->withQueryString();

        return view('mentor.kehadiran.index', compact('pengajuan', 'jumlah', 'status', 'jenis'));
    }

    /** UC-09 langkah 13–15 / UC-11 langkah 12–15 */
    public function setujui(Request $request, PengajuanKehadiran $pengajuanKehadiran): RedirectResponse
    {
        $peserta = $pengajuanKehadiran->pesertaMagang;
        $this->pastikanBimbingan($peserta);                                                        // E4
        $data = $request->validate(['catatan_keputusan' => ['nullable', 'string', 'max:2000']]);

        if ($pengajuanKehadiran->status !== 'Menunggu Verifikasi') {
            return back()->withErrors(['verifikasi' => 'Pengajuan ini sudah diverifikasi sebelumnya.']);
        }
        $tanggal = $pengajuanKehadiran->tanggal_kegiatan;
        if ($alasan = $this->kehadiran->alasanTidakBoleh($peserta, $tanggal)) {
            return back()->withErrors(['verifikasi' => $alasan]);                                    // UC-11 E3
        }
        if ($this->kehadiran->sudahTercatat($peserta, $tanggal)) {
            return back()->withErrors(['verifikasi' => 'Peserta sudah tercatat hadir pada tanggal tersebut, pengajuan tidak dapat disetujui.']);   // UC-09/11 E2
        }

        try {
            DB::transaction(function () use ($pengajuanKehadiran, $data) {
                $pengajuanKehadiran->update([
                    'status' => 'Disetujui',
                    'catatan_keputusan' => $data['catatan_keputusan'] ?? null,
                    'waktu_keputusan' => now(),
                ]);
                if ($pengajuanKehadiran->jenis === 'SUSULAN') {
                    // Susulan disetujui = 1 hari hadir; UNIQUE (id_peserta_magang, tanggal) mencegah kehadiran ganda.
                    Presensi::create([
                        'id_peserta_magang' => $pengajuanKehadiran->id_peserta_magang,
                        'id_pengajuan_kehadiran' => $pengajuanKehadiran->id_pengajuan_kehadiran,
                        'tanggal' => $pengajuanKehadiran->tanggal_kegiatan->toDateString(),
                        'sumber' => 'SUSULAN',
                        'waktu_checkin' => $pengajuanKehadiran->waktu_pengajuan,
                        'lat_checkin' => $pengajuanKehadiran->latitude,
                        'long_checkin' => $pengajuanKehadiran->longitude,
                        'akurasi_checkin' => $pengajuanKehadiran->akurasi_meter,
                    ]);
                }
            });
        } catch (UniqueConstraintViolationException) {
            return back()->withErrors(['verifikasi' => 'Peserta sudah tercatat hadir pada tanggal tersebut, pengajuan tidak dapat disetujui.']);
        }

        $this->beriTahuPeserta($pengajuanKehadiran, 'disetujui');

        return back()->with('sukses', 'Pengajuan '.strtolower($this->label($pengajuanKehadiran)).' '.$peserta->pengguna->nama_lengkap.' disetujui.');
    }

    /** UC-09 A1 / UC-11 A1 — tolak dengan catatan wajib. */
    public function tolak(Request $request, PengajuanKehadiran $pengajuanKehadiran): RedirectResponse
    {
        $this->pastikanBimbingan($pengajuanKehadiran->pesertaMagang);
        $data = $request->validate(['catatan_keputusan' => ['required', 'string', 'max:2000']],
            ['catatan_keputusan.required' => 'Catatan alasan penolakan wajib diisi.']);

        if ($pengajuanKehadiran->status !== 'Menunggu Verifikasi') {
            return back()->withErrors(['verifikasi' => 'Pengajuan ini sudah diverifikasi sebelumnya.']);
        }
        $pengajuanKehadiran->update([
            'status' => 'Ditolak',
            'catatan_keputusan' => $data['catatan_keputusan'],
            'waktu_keputusan' => now(),
        ]);
        $this->beriTahuPeserta($pengajuanKehadiran, 'ditolak');

        return back()->with('sukses', 'Pengajuan '.strtolower($this->label($pengajuanKehadiran)).' ditolak.');
    }

    /** Lihat bukti pendukung izin (disimpan privat, hanya mentor peserta tsb yang boleh membuka). */
    public function lampiran(PengajuanKehadiran $pengajuanKehadiran): StreamedResponse
    {
        $this->pastikanBimbingan($pengajuanKehadiran->pesertaMagang);
        $path = $pengajuanKehadiran->path_lampiran;
        abort_unless($path && Storage::disk('local')->exists($path), 404, 'Lampiran tidak ditemukan.');

        return Storage::disk('local')->response($path);
    }

    private function label(PengajuanKehadiran $p): string
    {
        return $p->jenis === 'IZIN' ? 'Izin' : 'Presensi susulan';
    }

    private function beriTahuPeserta(PengajuanKehadiran $p, string $hasil): void
    {
        Notifikasi::kirim($p->pesertaMagang->id_pengguna, 'VERIFIKASI_KEHADIRAN',
            $this->label($p).' tanggal '.$p->tanggal_kegiatan->format('d/m/Y').' '.$hasil.' oleh mentor.',
            'pengajuan_kehadiran', $p->id_pengajuan_kehadiran);
        Log::channel('aktivitas')->info('verifikasi_kehadiran', ['id' => $p->id_pengajuan_kehadiran, 'hasil' => $hasil, 'oleh' => auth()->id()]);
    }
}
