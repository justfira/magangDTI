<?php

namespace App\Http\Controllers\Direktur;

use App\Http\Controllers\Controller;
use App\Models\KeputusanPenerimaan;
use App\Models\Pengguna;
use App\Support\Notifikasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Keputusan Penerimaan (UC-05c)
 * Aktor  : Direktur DTI
 * Tabel  : keputusan_penerimaan (baca + tulis), pengajuan (update status akhir)
 *
 * Aturan:
 *  - Direktur berwenang memutuskan final (Diterima/Ditolak), boleh berbeda dari rekomendasi KaSi.
 *  - Kolom rekomendasi KaSi TIDAK diubah sama sekali.
 *  - Keputusan hanya untuk baris yang rekomendasi KaSi sudah ada (waktu_rekomendasi NOT NULL)
 *    dan Direktur belum memutuskan (keputusan IS NULL).
 *  - peserta_magang TIDAK dibuat di sini — dibuat Superadmin via UC-06.
 *  - pengajuan.status_pengajuan → 'Selesai Diputuskan' hanya setelah
 *    SEMUA calon dalam pengajuan mendapat keputusan.
 */
class KeputusanPenerimaanController extends Controller
{
    private const TAB = [
        'menunggu' => 'Menunggu Keputusan',
        'riwayat'  => 'Riwayat',
    ];

    // =========================================================================
    // index() — Daftar keputusan penerimaan
    // =========================================================================

    public function index(Request $request): View
    {
        $pegawai = $this->pegawaiLogin();
        abort_if($pegawai === null, 403, 'Akun Anda tidak terdaftar sebagai pegawai.');

        $tab  = array_key_exists($request->query('tab'), self::TAB)
              ? $request->query('tab')
              : 'menunggu';

        $cari = $request->string('cari')->trim()->value();

        $keputusan = KeputusanPenerimaan::with([
                'calon:id_calon,id_pengajuan,nama_lengkap,nomor_induk_pendidikan,email,no_hp,jurusan_prodi,institusi_asal',
                'calon.pengajuan:id_pengajuan,kode_pengajuan,jalur,nama_institusi,status_pengajuan,diajukan_oleh,waktu_pemeriksaan',
                'calon.pengajuan.diajukanOleh:id_pengguna,nama_lengkap',
                'kepalaSeksi.pengguna:id_pengguna,nama_lengkap',
                'direktur.pengguna:id_pengguna,nama_lengkap',
            ])
            ->whereNotNull('waktu_rekomendasi')
            ->when($tab === 'menunggu', fn (Builder $q) => $q->whereNull('keputusan')
                ->orderBy('waktu_rekomendasi'))
            ->when($tab === 'riwayat', fn (Builder $q) => $q->whereNotNull('keputusan')
                ->orderByDesc('waktu_keputusan'))
            ->when($cari !== '', fn (Builder $q) =>
                $q->whereHas('calon', fn (Builder $cq) =>
                    $cq->where('nama_lengkap', 'like', "%{$cari}%")
                       ->orWhere('nomor_induk_pendidikan', 'like', "%{$cari}%")
                       ->orWhereHas('pengajuan', fn (Builder $pq) =>
                           $pq->where('kode_pengajuan', 'like', "%{$cari}%")
                              ->orWhere('nama_institusi', 'like', "%{$cari}%")
                       )
                )
            )
            ->paginate(10)
            ->withQueryString();

        $jumlahMenunggu = KeputusanPenerimaan::whereNotNull('waktu_rekomendasi')
            ->whereNull('keputusan')
            ->count();

        return view('direktur.penerimaan.index', [
            'keputusan'      => $keputusan,
            'tab'            => $tab,
            'daftarTab'      => self::TAB,
            'jumlahMenunggu' => $jumlahMenunggu,
            'cari'           => $cari,
        ]);
    }

    // =========================================================================
    // update() — Simpan keputusan Direktur
    // =========================================================================

    /**
     * Simpan Keputusan Penerimaan — PUT /direktur/keputusan-penerimaan/{keputusan}
     *
     *  A1 Tolak jika rekomendasi KaSi belum ada (waktu_rekomendasi NULL).
     *  A2 Tolak jika sudah pernah diputuskan (keputusan NOT NULL).
     *  Concurrency: baris dikunci dengan lockForUpdate() di dalam transaksi.
     *  Status pengajuan → 'Selesai Diputuskan' hanya jika SEMUA calon sudah diputuskan.
     *  TIDAK membuat peserta_magang.
     */
    public function update(Request $request, KeputusanPenerimaan $keputusan): RedirectResponse
    {
        // ── Guard: hanya pegawai terdaftar ────────────────────────────────
        $direktur = $this->pegawaiLogin();
        abort_if($direktur === null, 403, 'Akun Anda tidak terdaftar sebagai pegawai.');

        // ── A1: rekomendasi KaSi harus sudah ada ──────────────────────────
        abort_if($keputusan->waktu_rekomendasi === null, 403,
            'Keputusan hanya dapat diberikan setelah Kepala Seksi memberikan rekomendasi.');

        // ── A2: belum boleh diputuskan ulang (pre-check sebelum validasi) ──
        if ($keputusan->keputusan !== null) {
            return redirect()
                ->route('direktur.penerimaan.index', ['tab' => 'riwayat'])
                ->with('gagal', 'Keputusan untuk calon ini sudah pernah diberikan sebelumnya.');
        }

        // ── Validasi input (error bag per baris agar bisa tampil inline) ──
        $bagNama = 'keputusan_' . $keputusan->id_keputusan;
        $data = $request->validateWithBag($bagNama, [
            'keputusan'         => ['required', Rule::in(KeputusanPenerimaan::KEPUTUSAN)],
            'catatan_keputusan' => [
                'nullable',
                'required_if:keputusan,Ditolak',
                'string',
                'max:2000',
            ],
        ], [
            'keputusan.required'           => 'Pilih keputusan: Diterima atau Ditolak.',
            'keputusan.in'                 => 'Nilai keputusan tidak valid.',
            'catatan_keputusan.required_if'=> 'Catatan wajib diisi bila keputusan Ditolak.',
            'catatan_keputusan.max'        => 'Catatan keputusan maksimal 2000 karakter.',
        ]);

        // ── Transaksi dengan row-level lock ───────────────────────────────
        $berhasil = DB::transaction(function () use ($data, $keputusan, $direktur) {

            // Lock baris agar dua request bersamaan tidak menghasilkan keputusan ganda.
            $terkunci = KeputusanPenerimaan::whereKey($keputusan->id_keputusan)
                ->lockForUpdate()
                ->firstOrFail();

            // Guard final di dalam transaksi (setelah lock).
            if ($terkunci->keputusan !== null) {
                return false;   // sudah diputuskan oleh request lain
            }

            // Update kolom keputusan — JANGAN sentuh rekomendasi KaSi.
            $terkunci->update([
                'keputusan'          => $data['keputusan'],
                'catatan_keputusan'  => $data['catatan_keputusan'] ?? null,
                'id_direktur'        => $direktur->id_pegawai,
                'waktu_keputusan'    => now(),
            ]);

            // Reload relasi calon → pengajuan agar data fresh.
            $terkunci->load('calon.pengajuan.calonPeserta');
            $pengajuan = $terkunci->calon?->pengajuan;

            if ($pengajuan) {
                // Hitung total calon dan yang sudah diputuskan dalam pengajuan ini.
                $idCalon      = $pengajuan->calonPeserta->pluck('id_calon');
                $totalCalon   = $idCalon->count();
                $sudahDiputus = KeputusanPenerimaan::whereIn('id_calon', $idCalon)
                    ->whereNotNull('keputusan')
                    ->count();

                // Ubah status pengajuan hanya jika SEMUA calon sudah diputuskan.
                if ($totalCalon > 0 && $sudahDiputus === $totalCalon) {
                    $pengajuan->update(['status_pengajuan' => 'Selesai Diputuskan']);
                }
            }

            return true;
        });

        // ── Tangani race condition ─────────────────────────────────────────
        if (! $berhasil) {
            return redirect()
                ->route('direktur.penerimaan.index', ['tab' => 'riwayat'])
                ->with('gagal', 'Keputusan untuk calon ini sudah diproses oleh sesi lain.');
        }

        // ── Notifikasi (di luar transaksi — kegagalan notif tidak rollback data) ──
        $keputusan->refresh()->load([
            'calon.pengajuan.diajukanOleh',
            'kepalaSeksi.pengguna',
            'direktur.pengguna',
        ]);
        $this->kirimNotifikasi($keputusan);

        Log::channel('aktivitas')->info('keputusan_penerimaan_disimpan', [
            'id_keputusan' => $keputusan->id_keputusan,
            'id_calon'     => $keputusan->id_calon,
            'keputusan'    => $keputusan->keputusan,
            'oleh'         => auth()->id(),
        ]);

        $nama = $keputusan->calon?->nama_lengkap ?? "calon #{$keputusan->id_calon}";

        return redirect()
            ->route('direktur.penerimaan.index', ['tab' => 'riwayat'])
            ->with('sukses', "Keputusan \"{$keputusan->keputusan}\" untuk {$nama} berhasil disimpan.");
    }

    // =========================================================================
    // Helper
    // =========================================================================

    /**
     * Kirim notifikasi sesuai keputusan:
     *  - Diterima → Superadmin aktif (untuk aktivasi akun via UC-06) + pendaftar (jika punya akun)
     *  - Ditolak  → pendaftar (jika punya akun)
     *
     * Kegagalan notifikasi individual TIDAK menghentikan proses.
     */
    private function kirimNotifikasi(KeputusanPenerimaan $keputusan): void
    {
        $nama    = $keputusan->calon?->nama_lengkap ?? 'calon peserta';
        $kode    = $keputusan->calon?->pengajuan?->kode_pengajuan ?? "calon #{$keputusan->id_calon}";
        $diterima = $keputusan->keputusan === 'Diterima';
        $idK     = $keputusan->id_keputusan;

        // Notifikasi Superadmin jika Diterima — untuk segera aktivasi akun peserta.
        if ($diterima) {
            $this->superadminAktif()->each(function (Pengguna $admin) use ($nama, $kode, $idK) {
                try {
                    Notifikasi::kirim(
                        $admin->id_pengguna,
                        'PESERTA_DITERIMA',
                        "Direktur menerima {$nama} (pengajuan {$kode}). "
                            . 'Aktifkan akun peserta di menu Aktivasi Peserta.',
                        'keputusan_penerimaan',
                        $idK,
                    );
                } catch (\Throwable) { /* notifikasi gagal tidak memblokir */ }
            });
        }

        // Notifikasi pendaftar (hanya jika jalur DTI dan punya akun).
        $idPendaftar = $keputusan->calon?->pengajuan?->diajukanOleh?->id_pengguna;
        if ($idPendaftar) {
            try {
                Notifikasi::kirim(
                    $idPendaftar,
                    $diterima ? 'PESERTA_DITERIMA' : 'PESERTA_DITOLAK',
                    $diterima
                        ? "Selamat! Pengajuan Anda ({$kode}) diterima. "
                            . 'Superadmin akan mengaktifkan akun peserta Anda segera.'
                        : "Mohon maaf, pengajuan Anda ({$kode}) tidak diterima. "
                            . ($keputusan->catatan_keputusan
                                ? "Catatan Direktur: {$keputusan->catatan_keputusan}"
                                : ''),
                    'keputusan_penerimaan',
                    $idK,
                );
            } catch (\Throwable) { /* notifikasi gagal tidak memblokir */ }
        }
    }

    /** Semua akun Superadmin yang aktif. */
    private function superadminAktif()
    {
        return Pengguna::where('status_akun', 'Aktif')
            ->whereHas('peran', fn (Builder $q) => $q->where('kode_peran', 'SUPERADMIN'))
            ->get();
    }
}
