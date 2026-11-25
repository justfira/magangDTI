<?php

namespace App\Http\Controllers\KepalaSeksi;

use App\Http\Controllers\Controller;
use App\Models\CalonPeserta;
use App\Models\KeputusanPenerimaan;
use App\Models\Pengguna;
use App\Support\Notifikasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Rekomendasi Penerimaan (UC-05b)
 * Aktor  : Kepala Seksi DTI
 * Tabel  : keputusan_penerimaan (tulis), calon_peserta + pengajuan (baca)
 *
 * Aturan:
 *  - Hanya calon dari pengajuan berstatus 'Lengkap' yang belum direkomendasikan.
 *  - KaSi tidak difilter per seksi pada tahap ini (pembatasan seksi ada di UC-07 Penempatan).
 *  - Direktur berwenang override rekomendasi — tidak ada pembatasan dari sistem.
 */
class RekomendasiPenerimaanController extends Controller
{
    private const TAB = [
        'menunggu' => 'Menunggu Rekomendasi',
        'riwayat'  => 'Riwayat',
    ];

    // =========================================================================
    // index() — Daftar calon peserta
    // =========================================================================

    public function index(Request $request): View
    {
        $pegawai = $this->pegawaiLogin();
        abort_if($pegawai === null, 403, 'Akun Anda tidak terdaftar sebagai pegawai.');

        $tab  = array_key_exists($request->query('tab'), self::TAB)
              ? $request->query('tab')
              : 'menunggu';

        $cari = $request->string('cari')->trim()->value();

        $calon = CalonPeserta::with([
                'pengajuan:id_pengajuan,kode_pengajuan,jalur,nama_institusi,status_pengajuan,tanggal_pengajuan,waktu_pemeriksaan,diajukan_oleh',
                'pengajuan.diajukanOleh:id_pengguna,nama_lengkap',
                'keputusanPenerimaan.kepalaSeksi.pengguna:id_pengguna,nama_lengkap',
            ])
            ->when($tab === 'menunggu', fn (Builder $q) =>
                $q->whereHas('pengajuan', fn (Builder $pq) =>
                    $pq->where('status_pengajuan', 'Lengkap')
                )
                ->doesntHave('keputusanPenerimaan')
            )
            ->when($tab === 'riwayat', fn (Builder $q) =>
                $q->whereHas('keputusanPenerimaan', fn (Builder $kq) =>
                    $kq->where('id_kepala_seksi', $pegawai->id_pegawai)
                )
            )
            ->when($cari !== '', fn (Builder $q) =>
                $q->where(fn (Builder $qq) =>
                    $qq->where('nama_lengkap', 'like', "%{$cari}%")
                       ->orWhere('nomor_induk_pendidikan', 'like', "%{$cari}%")
                       ->orWhereHas('pengajuan', fn (Builder $pq) =>
                           $pq->where('kode_pengajuan', 'like', "%{$cari}%")
                              ->orWhere('nama_institusi', 'like', "%{$cari}%")
                       )
                )
            )
            ->orderByDesc('id_calon')
            ->paginate(15)
            ->withQueryString();

        $jumlahMenunggu = CalonPeserta::whereHas('pengajuan', fn (Builder $q) =>
                $q->where('status_pengajuan', 'Lengkap')
            )
            ->doesntHave('keputusanPenerimaan')
            ->count();

        return view('kepala-seksi.rekomendasi.index', [
            'calon'          => $calon,
            'tab'            => $tab,
            'daftarTab'      => self::TAB,
            'jumlahMenunggu' => $jumlahMenunggu,
            'cari'           => $cari,
            'daftarRek'      => KeputusanPenerimaan::REKOMENDASI,
        ]);
    }

    // =========================================================================
    // store() — Simpan rekomendasi
    // =========================================================================

    /**
     * Simpan Rekomendasi — POST /kepala-seksi/rekomendasi-penerimaan/{calon}
     *
     *  A1 Tolak jika pengajuan belum berstatus 'Lengkap'.
     *  A2 Tolak jika calon sudah punya keputusan_penerimaan.
     */
    public function store(Request $request, CalonPeserta $calon): RedirectResponse
    {
        // ── Guard: hanya pegawai terdaftar ────────────────────────────────
        $pegawai = $this->pegawaiLogin();
        abort_if($pegawai === null, 403, 'Akun Anda tidak terdaftar sebagai pegawai.');

        // ── A1: pengajuan harus berstatus Lengkap ─────────────────────────
        $calon->load('pengajuan:id_pengajuan,kode_pengajuan,status_pengajuan');
        if ($calon->pengajuan?->status_pengajuan !== 'Lengkap') {
            return back()->with('gagal',
                'Rekomendasi hanya dapat diberikan untuk calon dari pengajuan yang sudah berstatus Lengkap.'
            );
        }

        // ── A2: belum boleh direkomendasikan ulang ────────────────────────
        if ($calon->keputusanPenerimaan()->exists()) {
            return back()->with('gagal',
                "Calon {$calon->nama_lengkap} sudah mendapat rekomendasi sebelumnya."
            );
        }

        // ── Validasi input ─────────────────────────────────────────────────
        $data = $request->validate([
            'rekomendasi'          => ['required', 'in:Terima,Tolak'],
            'catatan_rekomendasi'  => ['nullable', 'string', 'max:2000'],
        ], [
            'rekomendasi.required' => 'Pilih rekomendasi: Terima atau Tolak.',
            'rekomendasi.in'       => 'Nilai rekomendasi tidak valid.',
            'catatan_rekomendasi.max' => 'Catatan maksimal 2000 karakter.',
        ]);

        // ── Simpan & notifikasi ────────────────────────────────────────────
        try {
            DB::transaction(function () use ($calon, $data, $pegawai) {
                KeputusanPenerimaan::create([
                    'id_calon'             => $calon->id_calon,
                    'id_kepala_seksi'      => $pegawai->id_pegawai,
                    'rekomendasi'          => $data['rekomendasi'],
                    'catatan_rekomendasi'  => $data['catatan_rekomendasi'] ?? null,
                    // waktu_rekomendasi → useCurrent() di DB
                ]);
            });

        } catch (Throwable $e) {
            Log::error('rekomendasi_penerimaan_gagal', [
                'id_calon' => $calon->id_calon,
                'error'    => $e->getMessage(),
                'oleh'     => auth()->id(),
            ]);
            return back()->withInput()
                ->with('gagal', 'Penyimpanan rekomendasi gagal. Silakan coba lagi.');
        }

        // ── Notifikasi ke semua Direktur aktif ────────────────────────────
        $kode = $calon->pengajuan?->kode_pengajuan ?? "calon #{$calon->id_calon}";
        foreach ($this->direkturAktif() as $dir) {
            Notifikasi::kirim(
                $dir->id_pengguna,
                'REKOMENDASI_PENERIMAAN',
                "{$pegawai->pengguna->nama_lengkap} merekomendasikan {$data['rekomendasi']} untuk "
                    . "{$calon->nama_lengkap} (pengajuan {$kode}).",
                'keputusan_penerimaan',
                $calon->keputusanPenerimaan()->value('id_keputusan'),
            );
        }

        Log::channel('aktivitas')->info('rekomendasi_penerimaan_disimpan', [
            'id_calon'    => $calon->id_calon,
            'rekomendasi' => $data['rekomendasi'],
            'oleh'        => auth()->id(),
        ]);

        return redirect()
            ->route('kasi.rekomendasi.index', ['tab' => 'riwayat'])
            ->with('sukses',
                "Rekomendasi \"{$data['rekomendasi']}\" untuk {$calon->nama_lengkap} berhasil disimpan."
            );
    }

    // =========================================================================
    // Helper
    // =========================================================================

    /** Semua akun Direktur yang aktif (penerima notifikasi). */
    private function direkturAktif()
    {
        return Pengguna::where('status_akun', 'Aktif')
            ->whereHas('peran', fn (Builder $q) => $q->where('kode_peran', 'DIREKTUR'))
            ->get();
    }
}
