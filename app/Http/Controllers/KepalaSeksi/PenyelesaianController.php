<?php

namespace App\Http\Controllers\KepalaSeksi;

use App\Http\Controllers\Controller;
use App\Models\PenyelesaianMagang;
use App\Models\Pengguna;
use App\Models\PesertaMagang;
use App\Support\Notifikasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Rekomendasi Penyelesaian Magang (UC-14) — SUDAH DIIMPLEMENTASIKAN.
 * Aktor  : Kepala Seksi DTI
 * Tabel  : penyelesaian_magang (tulis); peserta_magang, tugas, presensi, logbook, evaluasi_peserta (baca)
 *
 * Aturan:
 *  - Kasi hanya memproses peserta di seksinya sendiri.
 *  - Rekomendasi baru ditolak bila peserta sudah punya penyelesaian 'Layak Diselesaikan' yang belum
 *    diputuskan, atau penyelesaian yang sudah 'Disetujui'. 'Perlu Tindak Lanjut' dan 'Ditolak' tidak menghalangi.
 *  - Hanya 'Layak Diselesaikan' yang dikirim ke Direktur; 'Perlu Tindak Lanjut' cukup tercatat.
 *  - Evaluasi mentor yang belum ada / belum memenuhi hanya menjadi peringatan, tidak ditolak.
 */
class PenyelesaianController extends Controller
{
    private const TAB = ['aktif' => 'Sedang Berjalan', 'ditinjau' => 'Waktunya Ditinjau', 'selesai' => 'Selesai', 'semua' => 'Semua'];

    /** Batas hari menjelang tanggal_selesai untuk penanda "Berakhir dalam N hari" dan tab Waktunya Ditinjau. */
    private const HARI_MENJELANG = 14;

    /**
     * Penyelesaian Magang — GET /kepala-seksi/penyelesaian
     * Daftar peserta seksi sendiri (langsung dari peserta_magang) beserta ringkasan tugas, kehadiran,
     * logbook, evaluasi, dan penanda waktu berdasarkan peserta_magang.tanggal_selesai.
     */
    public function index(Request $request): View
    {
        $idSeksi = $this->seksiKasi();
        $tab = array_key_exists($request->query('tab'), self::TAB) ? $request->query('tab') : 'aktif';

        $peserta = $this->denganRingkasan(PesertaMagang::query()->where('id_seksi', $idSeksi))
            ->when($tab === 'aktif', fn (Builder $q) => $q->where('status_magang', 'Aktif'))
            ->when($tab === 'ditinjau', fn (Builder $q) => $q->where('status_magang', 'Aktif')
                ->whereDate('tanggal_selesai', '<=', today()->addDays(self::HARI_MENJELANG))
                ->whereDoesntHave('penyelesaianMagang', fn (Builder $p) => $p->where(fn (Builder $g) => $this->filterPenghalang($g))))
            ->when($tab === 'selesai', fn (Builder $q) => $q->where('status_magang', 'Selesai'))
            ->when($tab === 'semua', fn (Builder $q) => $q->whereIn('status_magang', ['Aktif', 'Selesai']))
            ->with(['pengguna', 'keputusan.calon', 'mentor.pengguna', 'evaluasiPeserta', 'penyelesaianMagang'])
            ->orderBy('tanggal_selesai')
            ->orderBy('id_peserta_magang')
            ->paginate(10)
            ->withQueryString();

        // Penanda dihitung di server dari data yang sudah di-eager-load (tanpa query tambahan).
        $penanda = $peserta->getCollection()
            ->mapWithKeys(fn (PesertaMagang $p) => [$p->id_peserta_magang => $this->penandaWaktu($p)]);

        return view('kepala-seksi.penyelesaian.index', [
            'peserta' => $peserta,
            'penanda' => $penanda,
            'tab' => $tab,
            'daftarTab' => self::TAB,
            'hariMenjelang' => self::HARI_MENJELANG,
        ]);
    }

    /**
     * Tinjau Peserta — GET /kepala-seksi/penyelesaian/{peserta}
     * Ringkasan kegiatan, evaluasi mentor (peringatan bila belum memenuhi), riwayat penyelesaian, form rekomendasi.
     */
    public function show(Request $request, PesertaMagang $peserta): View
    {
        $this->pastikanSeksiSendiri($peserta);

        $peserta = $this->denganRingkasan(PesertaMagang::query()->whereKey($peserta->id_peserta_magang))
            ->with(['pengguna', 'seksi', 'mentor.pengguna', 'keputusan.calon', 'evaluasiPeserta'])
            ->firstOrFail();

        $riwayat = $peserta->penyelesaianMagang()
            ->with(['kepalaSeksi.pengguna', 'direktur.pengguna'])
            ->latest('id_penyelesaian')
            ->get();

        $evaluasi = $peserta->evaluasiPeserta;
        $peringatanEvaluasi = match (true) {
            $evaluasi === null => 'Mentor belum mengisi evaluasi untuk peserta ini.',
            in_array($evaluasi->status_evaluasi, ['Belum Memenuhi', 'Belum Dinilai'], true) => "Status evaluasi mentor: {$evaluasi->status_evaluasi}.",
            default => null,
        };

        return view('kepala-seksi.penyelesaian.show', [
            'peserta' => $peserta,
            'calon' => $peserta->keputusan?->calon,
            'evaluasi' => $evaluasi,
            'peringatanEvaluasi' => $peringatanEvaluasi,
            'riwayat' => $riwayat,
            'alasanTidakBoleh' => $this->alasanTidakBolehRekomendasi($peserta),
            'infoMasaMagang' => $this->infoMasaMagang($peserta),
        ]);
    }

    /**
     * Simpan Rekomendasi Penyelesaian — POST /kepala-seksi/penyelesaian/{peserta}
     * Layak Diselesaikan / Perlu Tindak Lanjut + catatan (wajib); buat penyelesaian_magang;
     * notifikasi ke Direktur hanya untuk 'Layak Diselesaikan'.
     */
    public function store(Request $request, PesertaMagang $peserta): RedirectResponse
    {
        $this->pastikanSeksiSendiri($peserta);

        $data = $request->validate([
            'rekomendasi' => ['required', Rule::in(PenyelesaianMagang::REKOMENDASI)],
            'catatan_rekomendasi' => ['required', 'string', 'max:2000'],
        ], [
            'rekomendasi.required' => 'Pilih rekomendasi penyelesaian.',
            'rekomendasi.in' => 'Rekomendasi tidak valid.',
            'catatan_rekomendasi.required' => 'Catatan rekomendasi wajib diisi.',
            'catatan_rekomendasi.max' => 'Catatan rekomendasi maksimal 2000 karakter.',
        ]);

        $penyelesaian = DB::transaction(function () use ($data, $peserta) {
            // Kunci baris peserta agar dua kiriman bersamaan tidak sama-sama lolos pemeriksaan.
            $terkunci = PesertaMagang::whereKey($peserta->id_peserta_magang)->lockForUpdate()->firstOrFail();
            if ($alasan = $this->alasanTidakBolehRekomendasi($terkunci)) {
                return $alasan;
            }

            return $terkunci->penyelesaianMagang()->create([
                'id_kepala_seksi' => $this->pegawaiLogin()->id_pegawai,
                'rekomendasi' => $data['rekomendasi'],
                'catatan_rekomendasi' => $data['catatan_rekomendasi'],
                'waktu_rekomendasi' => now(),
            ]);
        });

        if (is_string($penyelesaian)) {
            return back()->withInput()->withErrors(['rekomendasi' => $penyelesaian]);
        }

        $namaPeserta = $peserta->pengguna?->nama_lengkap ?? 'peserta';
        if ($penyelesaian->rekomendasi === 'Layak Diselesaikan') {
            $this->direkturAktif()->each(fn (Pengguna $direktur) => Notifikasi::kirim(
                $direktur->id_pengguna,
                'REKOMENDASI_PENYELESAIAN',
                "Rekomendasi penyelesaian magang untuk {$namaPeserta} menunggu keputusan Anda.",
                'penyelesaian_magang',
                $penyelesaian->id_penyelesaian,
            ));
        }

        Log::channel('aktivitas')->info('penyelesaian_direkomendasikan', [
            'id_penyelesaian' => $penyelesaian->id_penyelesaian,
            'id_peserta_magang' => $peserta->id_peserta_magang,
            'rekomendasi' => $penyelesaian->rekomendasi,
            'oleh' => auth()->id(),
        ]);

        $pesan = $penyelesaian->rekomendasi === 'Layak Diselesaikan'
            ? 'Rekomendasi penyelesaian disimpan dan dikirim ke Direktur.'
            : 'Rekomendasi "Perlu Tindak Lanjut" disimpan. Rekomendasi baru dapat dibuat setelah peserta menindaklanjuti.';

        return redirect()->route('kasi.penyelesaian.show', $peserta)->with('sukses', $pesan);
    }

    /** Menambah hitungan ringkasan (tugas, presensi, logbook) ke query peserta. */
    private function denganRingkasan(Builder $query): Builder
    {
        return $query->withCount([
            'tugas',
            'tugas as tugas_selesai_count' => fn (Builder $q) => $q->where('status', 'Selesai'),
            'presensi',
            'logbook',
            'logbook as logbook_disetujui_count' => fn (Builder $q) => $q->where('status', 'Disetujui'),
        ]);
    }

    /**
     * Null bila rekomendasi baru boleh dibuat; selain itu alasan penolakan (dipakai di server & tampilan).
     * Diblokir oleh: 'Layak Diselesaikan' yang belum diputuskan, atau keputusan 'Disetujui'.
     */
    private function alasanTidakBolehRekomendasi(PesertaMagang $peserta): ?string
    {
        if ($peserta->status_magang !== 'Aktif') {
            return "Rekomendasi hanya dapat dibuat untuk peserta berstatus Aktif (status saat ini: {$peserta->status_magang}).";
        }

        $penghalang = $peserta->penyelesaianMagang()
            ->where(fn (Builder $q) => $this->filterPenghalang($q))
            ->latest('id_penyelesaian')
            ->first();

        return match (true) {
            $penghalang === null => null,
            $penghalang->keputusan === 'Disetujui' => 'Penyelesaian magang peserta ini sudah disetujui Direktur.',
            default => 'Peserta ini masih memiliki rekomendasi "Layak Diselesaikan" yang menunggu keputusan Direktur.',
        };
    }

    /** Penyelesaian yang menghalangi rekomendasi baru: 'Layak Diselesaikan' yang belum diputuskan, atau yang 'Disetujui'. */
    private function filterPenghalang(Builder $q): Builder
    {
        return $q->where(fn (Builder $q) => $q->where('rekomendasi', 'Layak Diselesaikan')->whereNull('keputusan'))
            ->orWhere('keputusan', 'Disetujui');
    }

    /**
     * Penanda waktu untuk daftar: [kelas CSS, teks], atau null bila peserta tidak perlu penanda
     * (bukan Aktif, sedang menunggu keputusan Direktur, atau sudah Disetujui).
     * Memakai relasi penyelesaianMagang yang sudah di-eager-load.
     */
    private function penandaWaktu(PesertaMagang $peserta): ?array
    {
        if ($peserta->status_magang !== 'Aktif' || $peserta->tanggal_selesai === null) {
            return null;
        }

        $terhalang = $peserta->penyelesaianMagang->contains(fn (PenyelesaianMagang $p) => ($p->rekomendasi === 'Layak Diselesaikan' && $p->keputusan === null) || $p->keputusan === 'Disetujui');
        if ($terhalang) {
            return null;
        }

        $sisaHari = (int) today()->diffInDays($peserta->tanggal_selesai->copy()->startOfDay(), false);

        return match (true) {
            $sisaHari <= 0 => ['status-peringatan', 'Sudah waktunya ditinjau'],
            $sisaHari <= self::HARI_MENJELANG => ['status-info', "Berakhir dalam {$sisaHari} hari"],
            default => ['status-netral', 'Masih berjalan'],
        };
    }

    /** Keterangan masa magang di halaman tinjau (hanya informasi; penyimpanan rekomendasi tidak ditolak). */
    private function infoMasaMagang(PesertaMagang $peserta): ?array
    {
        if ($peserta->status_magang !== 'Aktif' || $peserta->tanggal_selesai === null) {
            return null;
        }

        return $peserta->tanggal_selesai->copy()->startOfDay()->gt(today())
            ? ['peringatan', 'Masa magang belum berakhir (berakhir pada '.$peserta->tanggal_selesai->format('d/m/Y').').']
            : ['info', 'Masa magang sudah berakhir, silakan tinjau.'];
    }

    /** id_seksi milik Kepala Seksi yang login; 403 bila tidak terdaftar di seksi mana pun. */
    private function seksiKasi(): int
    {
        $idSeksi = $this->pegawaiLogin()?->id_seksi;
        abort_if($idSeksi === null, 403, 'Akun Anda belum terdaftar pada seksi mana pun.');

        return (int) $idSeksi;
    }

    /** Kasi hanya boleh memproses peserta di seksinya sendiri. */
    private function pastikanSeksiSendiri(PesertaMagang $peserta): void
    {
        abort_unless($peserta->id_seksi !== null && (int) $peserta->id_seksi === $this->seksiKasi(), 403, 'Peserta ini bukan bagian dari seksi Anda.');
    }

    /** Semua akun Direktur yang aktif (penerima notifikasi rekomendasi). */
    private function direkturAktif()
    {
        return Pengguna::where('status_akun', 'Aktif')
            ->whereHas('peran', fn (Builder $q) => $q->where('kode_peran', 'DIREKTUR'))
            ->get();
    }
}
