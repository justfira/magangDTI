<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\PengajuanKehadiran;
use App\Services\KehadiranService;
use App\Support\Notifikasi;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pengajuan Izin & Presensi Susulan (UC-09 / UC-11)
 * Aktor  : Peserta Magang
 * Tabel  : pengajuan_kehadiran
 */
class PengajuanKehadiranController extends Controller
{
    public function __construct(private KehadiranService $kehadiran)
    {
    }

    /** Riwayat pengajuan izin & presensi susulan milik peserta beserta hasil verifikasi mentor. */
    public function index(Request $request): View
    {
        $peserta = $this->pesertaLogin();
        abort_unless($peserta, 403, 'Anda belum terdaftar sebagai peserta magang.');

        $pengajuan = $peserta->pengajuanKehadiran()->latest('id_pengajuan_kehadiran')->paginate(15);

        return view('peserta.kehadiran.index', compact('pengajuan'));
    }

    /** Form pengajuan: ?jenis=SUSULAN atau ?jenis=IZIN */
    public function create(Request $request): View
    {
        $peserta = $this->pesertaLogin();
        abort_unless($peserta, 403, 'Anda belum terdaftar sebagai peserta magang.');
        $jenis = $request->query('jenis') === 'IZIN' ? 'IZIN' : 'SUSULAN';

        return view('peserta.kehadiran.create_'.strtolower($jenis), [
            'peserta' => $peserta,
            'alasanTidakBoleh' => $this->kehadiran->alasanTidakBoleh($peserta, today()),
            'sudahHadir' => $this->kehadiran->sudahTercatat($peserta, today()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $request->input('jenis') === 'IZIN'
            ? $this->simpanIzin($request)
            : $this->simpanSusulan($request);
    }

    /** UC-09 langkah 1–9: presensi susulan setelah kembali ke kantor pada hari yang sama. */
    private function simpanSusulan(Request $request): RedirectResponse
    {
        $peserta = $this->pesertaLogin();
        $data = $request->validate([
            'tanggal_kegiatan' => ['required', 'date'],
            'alasan' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'alasan.required' => 'Alasan tidak melakukan presensi reguler wajib diisi.',             // E5
            'alasan.min' => 'Alasan minimal 10 karakter.',
        ]);

        if (! today()->isSameDay($data['tanggal_kegiatan'])) {
            return back()->withInput()->withErrors(['tanggal_kegiatan' => 'Presensi susulan hanya dapat diajukan pada tanggal kegiatan yang sama (hari ini).']);   // E1
        }
        if ($alasan = $this->kehadiran->alasanTidakBoleh($peserta, today())) {
            return back()->withInput()->withErrors(['pengajuan' => $alasan]);
        }
        if ($this->kehadiran->sudahTercatat($peserta, today())) {
            return back()->withInput()->withErrors(['pengajuan' => 'Kehadiran Anda hari ini sudah tercatat, presensi susulan tidak diperlukan.']);   // E2
        }
        if ($this->kehadiran->adaPengajuanAktif($peserta, today())) {
            return back()->withInput()->withErrors(['pengajuan' => 'Sudah ada pengajuan izin/susulan untuk tanggal ini yang belum ditolak.']);
        }
        [$lokasi, $salahLokasi] = $this->kehadiran->periksaLokasi($request->only('latitude', 'longitude', 'akurasi'));
        if ($salahLokasi) {
            return back()->withInput()->withErrors(['pengajuan' => $salahLokasi]);                  // E3
        }

        $pengajuan = PengajuanKehadiran::create([
            'id_peserta_magang' => $peserta->id_peserta_magang,
            'jenis' => 'SUSULAN',
            'tanggal_kegiatan' => today()->toDateString(),
            'alasan' => $data['alasan'],
            'latitude' => $lokasi['latitude'],
            'longitude' => $lokasi['longitude'],
            'akurasi_meter' => $lokasi['akurasi'],
            'waktu_pengajuan' => now(),                                                           // waktu server
            'status' => 'Menunggu Verifikasi',
        ]);

        $this->beriTahuMentor($pengajuan, 'presensi susulan');

        return redirect()->route('peserta.kehadiran.index')->with('sukses', 'Presensi susulan berhasil diajukan dan menunggu verifikasi mentor.');
    }

    /** UC-11 langkah 1–8: pengajuan izin ketidakhadiran. */
    private function simpanIzin(Request $request): RedirectResponse
    {
        $peserta = $this->pesertaLogin();
        $data = $request->validate([
            'tanggal_kegiatan' => ['required', 'date'],
            'jenis_izin' => ['required', Rule::in(PengajuanKehadiran::JENIS_IZIN)],
            'alasan' => ['required', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],      // A2: lampiran opsional
        ], [
            'alasan.required' => 'Alasan izin wajib diisi.',                                     // E5
            'lampiran.mimes' => 'Lampiran harus berupa PDF, JPG, atau PNG.',
            'lampiran.max' => 'Ukuran lampiran maksimal 2 MB.',
        ]);
        $tanggal = Carbon::parse($data['tanggal_kegiatan'])->startOfDay();

        if ($alasan = $this->kehadiran->alasanTidakBoleh($peserta, $tanggal)) {
            return back()->withInput()->withErrors(['pengajuan' => $alasan]);                    // E1, E3
        }
        if ($this->kehadiran->sudahTercatat($peserta, $tanggal)) {
            return back()->withInput()->withErrors(['pengajuan' => 'Anda sudah tercatat hadir pada tanggal tersebut, izin tidak dapat diajukan.']);   // E2
        }
        if ($this->kehadiran->adaPengajuanAktif($peserta, $tanggal)) {
            return back()->withInput()->withErrors(['pengajuan' => 'Sudah ada pengajuan izin/susulan untuk tanggal ini yang belum ditolak.']);
        }

        $pengajuan = PengajuanKehadiran::create([
            'id_peserta_magang' => $peserta->id_peserta_magang,
            'jenis' => 'IZIN',
            'tanggal_kegiatan' => $tanggal->toDateString(),
            'jenis_izin' => $data['jenis_izin'],
            'alasan' => $data['alasan'],
            'keterangan' => $data['keterangan'] ?? null,
            // disk "local" = storage/app/private: tidak bisa diakses lewat URL publik
            'path_lampiran' => $request->file('lampiran')?->store('izin/'.$peserta->id_peserta_magang, 'local'),
            'waktu_pengajuan' => now(),
            'status' => 'Menunggu Verifikasi',
        ]);

        $this->beriTahuMentor($pengajuan, 'izin '.strtolower($pengajuan->jenis_izin));

        return redirect()->route('peserta.kehadiran.index')->with('sukses', 'Pengajuan izin berhasil dikirim dan menunggu verifikasi mentor.');
    }

    private function beriTahuMentor(PengajuanKehadiran $pengajuan, string $label): void
    {
        $peserta = $pengajuan->pesertaMagang;
        if ($idMentor = $peserta->mentor?->id_pengguna) {
            Notifikasi::kirim($idMentor, 'PENGAJUAN_KEHADIRAN',
                $peserta->pengguna->nama_lengkap.' mengajukan '.$label.' tanggal '.$pengajuan->tanggal_kegiatan->format('d/m/Y').'.',
                'pengajuan_kehadiran', $pengajuan->id_pengajuan_kehadiran);
        }
        Log::channel('aktivitas')->info('pengajuan_kehadiran', ['id' => $pengajuan->id_pengajuan_kehadiran, 'jenis' => $pengajuan->jenis]);
    }
}
