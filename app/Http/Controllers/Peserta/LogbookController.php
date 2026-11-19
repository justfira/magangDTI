<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Logbook;
use App\Support\Notifikasi;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Logbook Harian (Peserta) (UC-12)
 * Aktor  : Peserta Magang
 * Tabel  : logbook
 *
 * Status : IMPLEMENTASI — satu logbook per peserta per tanggal (UNIQUE). Hasil
 *          pemeriksaan terakhir disimpan pada baris logbook yang sama
 *          (catatan_mentor + waktu_periksa), tanpa tabel riwayat.
 */
class LogbookController extends Controller
{
    /** Peserta magang yang sedang berjalan milik pengguna login (Belum Mulai / Aktif). */
    private function pesertaBerjalan(): ?\App\Models\PesertaMagang
    {
        return auth()->user()?->pesertaMagangBerjalan();
    }

    /**
     * Logbook Harian — GET /peserta/logbook
     * Daftar logbook & hari hadir yang belum punya logbook.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $peserta = $this->pesertaBerjalan();
        if (! $peserta) {
            return redirect()->route('dashboard')->with('info', 'Logbook tersedia setelah Anda ditempatkan sebagai peserta magang.');
        }

        $logbook = $peserta->logbook()->orderByDesc('tanggal_kegiatan')->get();
        $tanggalTerisi = $logbook->map->tanggal_kegiatan->map->toDateString();

        // Tanggal izin penuh berstatus Disetujui tidak diwajibkan memiliki logbook (A2 bagian E).
        $izinPenuh = $peserta->pengajuanKehadiran()
            ->where('jenis', 'IZIN')
            ->where('jenis_izin', 'Penuh')
            ->where('status', 'Disetujui')
            ->pluck('tanggal_kegiatan')
            ->map->toDateString();

        // Hari hadir (ada presensi) yang belum punya logbook dan bukan izin penuh.
        $hariKosong = $peserta->presensi()
            ->orderByDesc('tanggal')
            ->get()
            ->filter(fn ($p) => ! $tanggalTerisi->contains($p->tanggal->toDateString())
                && ! $izinPenuh->contains($p->tanggal->toDateString()));

        return view('peserta.logbook.index', compact('peserta', 'logbook', 'hariKosong'));
    }

    /**
     * Isi Logbook — GET /peserta/logbook/create
     */
    public function create(Request $request): View|RedirectResponse
    {
        $peserta = $this->pesertaBerjalan();
        if (! $peserta || $peserta->status_magang !== 'Aktif') {
            return redirect()->route('dashboard')->with('info', 'Logbook hanya dapat diisi saat magang berstatus Aktif.');
        }

        return view('peserta.logbook.create', ['peserta' => $peserta]);
    }

    /**
     * Simpan Logbook — POST /peserta/logbook
     * Satu logbook per peserta per tanggal (UNIQUE) (A2). Notifikasi ke mentor.
     */
    public function store(Request $request): RedirectResponse
    {
        $peserta = $this->pesertaBerjalan();
        if (! $peserta || $peserta->status_magang !== 'Aktif') {
            return redirect()->route('dashboard')->with('info', 'Logbook hanya dapat dikirim saat magang berstatus Aktif.');
        }

        $data = $request->validate([
            'tanggal_kegiatan' => [
                'required', 'date',
                'after_or_equal:'.$peserta->tanggal_mulai->toDateString(),
                'before_or_equal:'.$peserta->tanggal_selesai->toDateString(),
                Rule::unique('logbook')->where(
                    fn ($q) => $q->where('id_peserta_magang', $peserta->id_peserta_magang)
                ),
            ],
            'uraian_aktivitas' => ['required', 'string', 'min:5', 'max:5000'],
        ], [
            'tanggal_kegiatan.required' => 'Tanggal kegiatan wajib diisi.',
            'tanggal_kegiatan.unique' => 'Logbook untuk tanggal ini sudah ada — buka daftar logbook untuk melihat atau memperbaikinya.',
            'tanggal_kegiatan.after_or_equal' => 'Tanggal kegiatan berada di luar periode magang Anda.',
            'tanggal_kegiatan.before_or_equal' => 'Tanggal kegiatan berada di luar periode magang Anda.',
            'uraian_aktivitas.required' => 'Uraian aktivitas wajib diisi.',
            'uraian_aktivitas.min' => 'Uraian aktivitas minimal 5 karakter.',
            'uraian_aktivitas.max' => 'Uraian aktivitas maksimal 5000 karakter.',
        ]);

        try {
            $logbook = $peserta->logbook()->create($data + [
                'status' => 'Dikirim',
                'waktu_kirim' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Perlindungan tambahan dari UNIQUE (id_peserta_magang, tanggal_kegiatan).
            return back()->withInput()->withErrors([
                'tanggal_kegiatan' => 'Logbook untuk tanggal ini sudah ada.',
            ]);
        }

        if ($peserta->mentor?->id_pengguna) {
            Notifikasi::kirim(
                $peserta->mentor->id_pengguna,
                'LOGBOOK_BARU',
                auth()->user()->nama_lengkap.' mengirim logbook tanggal '.$logbook->tanggal_kegiatan->translatedFormat('d M Y').'.',
                'logbook',
                $logbook->id_logbook,
            );
        }

        return redirect()->route('peserta.logbook.index')->with('sukses', 'Logbook terkirim untuk diperiksa mentor.');
    }

    /**
     * Detail Logbook — GET /peserta/logbook/{logbook}
     */
    public function show(Request $request, Logbook $logbook): View|RedirectResponse
    {
        $this->pastikanMilikSendiri($logbook->pesertaMagang);

        return view('peserta.logbook.show', ['logbook' => $logbook]);
    }

    /**
     * Perbaiki Logbook — GET /peserta/logbook/{logbook}/edit
     * Hanya untuk logbook milik sendiri berstatus Perlu Perbaikan (A1).
     */
    public function edit(Request $request, Logbook $logbook): View|RedirectResponse
    {
        $this->pastikanMilikSendiri($logbook->pesertaMagang);

        if ($logbook->status !== 'Perlu Perbaikan') {
            return redirect()->route('peserta.logbook.index')->with('info', 'Hanya logbook berstatus Perlu Perbaikan yang dapat diperbaiki.');
        }

        return view('peserta.logbook.edit', ['logbook' => $logbook]);
    }

    /**
     * Kirim Ulang Logbook — PUT /peserta/logbook/{logbook}
     * Perbarui uraian; status kembali Dikirim; hasil pemeriksaan terkini dibersihkan.
     */
    public function update(Request $request, Logbook $logbook): RedirectResponse
    {
        $this->pastikanMilikSendiri($logbook->pesertaMagang);

        if ($logbook->status !== 'Perlu Perbaikan') {
            return redirect()->route('peserta.logbook.index')->with('info', 'Hanya logbook berstatus Perlu Perbaikan yang dapat dikirim ulang.');
        }

        $data = $request->validate([
            'uraian_aktivitas' => ['required', 'string', 'min:5', 'max:5000'],
        ], [
            'uraian_aktivitas.required' => 'Uraian aktivitas wajib diisi.',
            'uraian_aktivitas.min' => 'Uraian aktivitas minimal 5 karakter.',
            'uraian_aktivitas.max' => 'Uraian aktivitas maksimal 5000 karakter.',
        ]);

        $logbook->update($data + [
            'status' => 'Dikirim',
            'waktu_kirim' => now(),
            'catatan_mentor' => null,   // hasil pemeriksaan terkini dibersihkan (desain satu hasil terakhir)
            'waktu_periksa' => null,
        ]);

        $mentor = $logbook->pesertaMagang->mentor;
        if ($mentor?->id_pengguna) {
            Notifikasi::kirim(
                $mentor->id_pengguna,
                'LOGBOOK_REVISI',
                auth()->user()->nama_lengkap.' mengirim ulang logbook tanggal '.$logbook->tanggal_kegiatan->translatedFormat('d M Y').'.',
                'logbook',
                $logbook->id_logbook,
            );
        }

        return redirect()->route('peserta.logbook.index')->with('sukses', 'Revisi logbook terkirim untuk diperiksa ulang.');
    }
}
