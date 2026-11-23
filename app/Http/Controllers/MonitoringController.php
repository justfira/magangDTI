<?php

namespace App\Http\Controllers;

use App\Models\PenyelesaianMagang;
use App\Models\PesertaMagang;
use App\Models\Seksi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Monitoring Program Magang (UC-16) — SUDAH DIIMPLEMENTASIKAN (hanya-baca).
 * Aktor  : Superadmin, Kepala Seksi, Direktur
 * Tabel  : peserta_magang, presensi, penyelesaian_magang (baca saja)
 *
 * Cakupan (A2): Superadmin & Direktur melihat semua seksi; Kepala Seksi hanya seksinya sendiri,
 * berlaku untuk semua kartu, tabel, dan filter.
 */
class MonitoringController extends Controller
{
    /**
     * Monitoring Program Magang — GET /monitoring
     * Query string: ?seksi={id_seksi}&status={status_magang}
     */
    public function index(Request $request): View
    {
        $seksiKasi = $this->seksiKasi();   // null = lihat semua seksi

        $daftarSeksi = Seksi::query()
            ->when($seksiKasi !== null, fn (Builder $q) => $q->whereKey($seksiKasi))
            ->orderBy('nama_seksi')
            ->get();

        // Filter dari query string; nilai di luar cakupan diabaikan.
        $filterSeksi = $seksiKasi ?? ($daftarSeksi->contains('id_seksi', (int) $request->query('seksi')) ? (int) $request->query('seksi') : null);
        $filterStatus = in_array($request->query('status'), PesertaMagang::STATUS, true) ? $request->query('status') : null;

        return view('monitoring.index', [
            'ringkasan' => $this->ringkasan($seksiKasi),
            'perSeksi' => $this->perSeksi($seksiKasi),
            'peserta' => $this->daftarPeserta($seksiKasi, $filterSeksi, $filterStatus),
            'daftarSeksi' => $daftarSeksi,
            'daftarStatus' => PesertaMagang::STATUS,
            'filterSeksi' => $filterSeksi,
            'filterStatus' => $filterStatus,
            'cakupanKasi' => $seksiKasi !== null,
        ]);
    }

    /** Kartu ringkasan (semua angka mengikuti cakupan seksi). */
    private function ringkasan(?int $seksiKasi): array
    {
        $peserta = fn () => $this->pesertaDalamCakupan($seksiKasi);
        $penyelesaian = fn () => PenyelesaianMagang::query()
            ->when($seksiKasi !== null, fn (Builder $q) => $q->whereHas('pesertaMagang', fn (Builder $p) => $p->where('id_seksi', $seksiKasi)));

        return [
            'Peserta aktif' => $peserta()->where('status_magang', 'Aktif')->count(),
            'Peserta selesai' => $peserta()->where('status_magang', 'Selesai')->count(),
            'Belum ditempatkan (tanpa mentor)' => $peserta()->whereIn('status_magang', ['Belum Mulai', 'Aktif'])->whereNull('id_mentor')->count(),
            // Sama dengan aturan form rekomendasi Kasi: Aktif & tidak punya 'Layak Diselesaikan' yang menunggu / yang Disetujui.
            'Menunggu rekomendasi Kasi' => $peserta()->where('status_magang', 'Aktif')
                ->whereDoesntHave('penyelesaianMagang', fn (Builder $q) => $q
                    ->where(fn (Builder $q) => $q->where('rekomendasi', 'Layak Diselesaikan')->whereNull('keputusan'))
                    ->orWhere('keputusan', 'Disetujui'))
                ->count(),
            'Menunggu keputusan Direktur' => $penyelesaian()->where('rekomendasi', 'Layak Diselesaikan')->whereNull('keputusan')->count(),
            'Penyelesaian disetujui' => $penyelesaian()->where('keputusan', 'Disetujui')->count(),
            'Penyelesaian ditolak' => $penyelesaian()->where('keputusan', 'Ditolak')->count(),
            'Sertifikat belum diunggah' => $penyelesaian()->where('keputusan', 'Disetujui')->whereNull('path_sertifikat')->count(),
        ];
    }

    /** Jumlah peserta per status untuk tiap seksi (+ baris "Belum ada seksi" untuk cakupan penuh). */
    private function perSeksi(?int $seksiKasi): array
    {
        $kolom = [];
        foreach (PesertaMagang::STATUS as $status) {
            $kolom['pesertaMagang as '.$this->aliasStatus($status)] = fn (Builder $q) => $q->where('status_magang', $status);
        }

        $baris = Seksi::query()
            ->when($seksiKasi !== null, fn (Builder $q) => $q->whereKey($seksiKasi))
            ->withCount($kolom)
            ->orderBy('nama_seksi')
            ->get()
            ->map(fn (Seksi $s) => ['nama' => $s->nama_seksi, 'jumlah' => $this->jumlahPerStatus($s)])
            ->all();

        if ($seksiKasi === null) {
            $tanpaSeksi = PesertaMagang::whereNull('id_seksi')
                ->selectRaw('status_magang, COUNT(*) as jumlah')
                ->groupBy('status_magang')
                ->pluck('jumlah', 'status_magang');
            if ($tanpaSeksi->sum() > 0) {
                $baris[] = [
                    'nama' => 'Belum ada seksi',
                    'jumlah' => collect(PesertaMagang::STATUS)->mapWithKeys(fn ($s) => [$s => (int) ($tanpaSeksi[$s] ?? 0)])->all(),
                ];
            }
        }

        return $baris;
    }

    /** Tabel peserta dengan hari hadir pada periode, presensi hari ini, dan penyelesaian terakhir (tanpa N+1). */
    private function daftarPeserta(?int $seksiKasi, ?int $filterSeksi, ?string $filterStatus)
    {
        return $this->pesertaDalamCakupan($seksiKasi)
            ->when($filterSeksi !== null, fn (Builder $q) => $q->where('id_seksi', $filterSeksi))
            ->when($filterStatus !== null, fn (Builder $q) => $q->where('status_magang', $filterStatus))
            ->with([
                'pengguna',
                'seksi',
                'mentor.pengguna',
                'penyelesaianMagang' => fn ($q) => $q->latest('id_penyelesaian'),
            ])
            ->withCount(['presensi as hadir_periode_count' => fn (Builder $q) => $q
                ->whereColumn('presensi.tanggal', '>=', 'peserta_magang.tanggal_mulai')
                ->whereColumn('presensi.tanggal', '<=', 'peserta_magang.tanggal_selesai')])
            ->withExists(['presensi as hadir_hari_ini' => fn (Builder $q) => $q->whereDate('tanggal', today())])
            ->orderByRaw("CASE status_magang WHEN 'Aktif' THEN 0 WHEN 'Belum Mulai' THEN 1 WHEN 'Selesai' THEN 2 ELSE 3 END")
            ->orderBy('tanggal_selesai')
            ->paginate(10)
            ->withQueryString();
    }

    private function pesertaDalamCakupan(?int $seksiKasi): Builder
    {
        return PesertaMagang::query()->when($seksiKasi !== null, fn (Builder $q) => $q->where('id_seksi', $seksiKasi));
    }

    /** id_seksi bila yang login Kepala Seksi (403 bila belum punya seksi); null untuk Superadmin/Direktur. */
    private function seksiKasi(): ?int
    {
        if (! auth()->user()->punyaPeran('KEPALA_SEKSI')) {
            return null;
        }
        $idSeksi = $this->pegawaiLogin()?->id_seksi;
        abort_if($idSeksi === null, 403, 'Akun Anda belum terdaftar pada seksi mana pun.');

        return (int) $idSeksi;
    }

    private function aliasStatus(string $status): string
    {
        return 'status_'.str_replace(' ', '_', strtolower($status)).'_count';
    }

    private function jumlahPerStatus(Seksi $seksi): array
    {
        return collect(PesertaMagang::STATUS)->mapWithKeys(fn ($s) => [$s => (int) $seksi->{$this->aliasStatus($s)}])->all();
    }
}
