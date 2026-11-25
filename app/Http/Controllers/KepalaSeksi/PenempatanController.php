<?php

namespace App\Http\Controllers\KepalaSeksi;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\PesertaMagang;
use App\Models\Seksi;
use App\Support\Notifikasi;
use App\Support\Peran as KodePeran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Penetapan Seksi & Mentor (UC-07)
 * Aktor  : Kepala Seksi DTI
 * Tabel  : peserta_magang, seksi, pegawai
 *
 * Aturan:
 *  - KaSi hanya boleh menempatkan peserta ke seksinya sendiri.
 *  - Mentor yang dipilih harus berperan MENTOR dan berasal dari seksi KaSi.
 *  - Peserta harus sudah diaktifkan (peserta_magang ada) dan belum punya id_seksi/id_mentor.
 *  - UC-07 TIDAK mengubah tanggal_mulai, tanggal_selesai, maupun status_magang.
 */
class PenempatanController extends Controller
{
    private const TAB = [
        'menunggu' => 'Menunggu Penempatan',
        'riwayat'  => 'Sudah Ditempatkan',
    ];

    // =========================================================================
    // index() — Daftar peserta menunggu atau sudah ditempatkan
    // =========================================================================

    /**
     * Daftar Penempatan — GET /kepala-seksi/penempatan
     *
     * Tab "menunggu": semua peserta aktif yang belum punya id_seksi dan id_mentor.
     * Tab "riwayat" : peserta yang ditempatkan ke seksi KaSi yang login.
     *
     * Query dibatasi berdasarkan seksi KaSi; KaSi dari seksi lain tidak bisa
     * melihat data riwayat penempatan seksi ini.
     */
    public function index(Request $request): View
    {
        // ── Guard: KaSi harus terdaftar sebagai pegawai dengan seksi ────────
        $pegawai = $this->pegawaiLogin();
        abort_if($pegawai === null, 403, 'Akun Anda tidak terdaftar sebagai pegawai.');
        abort_if($pegawai->id_seksi === null, 403,
            'Akun Anda belum memiliki seksi. Hubungi Superadmin untuk menetapkan seksi Anda.');

        $tab  = array_key_exists($request->query('tab'), self::TAB)
              ? $request->query('tab')
              : 'menunggu';

        $cari = $request->string('cari')->trim()->value();

        $peserta = PesertaMagang::with([
                'keputusan.calon:id_calon,id_pengajuan,nama_lengkap,email,institusi_asal,jurusan_prodi,nomor_induk_pendidikan',
                'keputusan.calon.pengajuan:id_pengajuan,kode_pengajuan,jalur,nama_institusi,diajukan_oleh',
                'keputusan.calon.pengajuan.diajukanOleh:id_pengguna,nama_lengkap',
                'pengguna:id_pengguna,nama_lengkap,email,status_akun',
                'seksi:id_seksi,nama_seksi',
                'mentor:id_pegawai,id_pengguna,id_seksi',
                'mentor.pengguna:id_pengguna,nama_lengkap',
                'ditetapkanOleh:id_pegawai,id_pengguna',
                'ditetapkanOleh.pengguna:id_pengguna,nama_lengkap',
            ])
            // ── Tab: Menunggu Penempatan ──────────────────────────────────
            ->when($tab === 'menunggu', fn (Builder $q) =>
                $q->whereNull('id_seksi')
                  ->whereNull('id_mentor')
                  ->where('status_magang', 'Belum Mulai')
                  ->whereHas('keputusan', fn (Builder $kq) =>
                      $kq->where('keputusan', 'Diterima')
                  )
                  ->whereHas('pengguna', fn (Builder $pq) =>
                      $pq->where('status_akun', 'Aktif')
                  )
                  ->orderBy('tanggal_mulai')
            )
            // ── Tab: Sudah Ditempatkan (hanya seksi KaSi ini) ────────────
            ->when($tab === 'riwayat', fn (Builder $q) =>
                $q->where('id_seksi', $pegawai->id_seksi)
                  ->whereNotNull('id_mentor')
                  ->orderByDesc('tanggal_penempatan')
            )
            // ── Pencarian ─────────────────────────────────────────────────
            ->when($cari !== '', fn (Builder $q) =>
                $q->whereHas('keputusan.calon', fn (Builder $cq) =>
                    $cq->where('nama_lengkap', 'like', "%{$cari}%")
                       ->orWhere('institusi_asal', 'like', "%{$cari}%")
                       ->orWhereHas('pengajuan', fn (Builder $pq) =>
                           $pq->where('nama_institusi', 'like', "%{$cari}%")
                       )
                )
            )
            ->paginate(15)
            ->withQueryString();

        // Badge hanya hitung yang menunggu (global — semua KaSi perlu tahu)
        $jumlahMenunggu = PesertaMagang::whereNull('id_seksi')
            ->whereNull('id_mentor')
            ->where('status_magang', 'Belum Mulai')
            ->whereHas('keputusan', fn (Builder $q) => $q->where('keputusan', 'Diterima'))
            ->whereHas('pengguna', fn (Builder $q) => $q->where('status_akun', 'Aktif'))
            ->count();

        // Data seksi KaSi untuk ditampilkan di halaman
        $seksiKasi = Seksi::find($pegawai->id_seksi);

        return view('kepala-seksi.penempatan.index', [
            'peserta'        => $peserta,
            'tab'            => $tab,
            'daftarTab'      => self::TAB,
            'jumlahMenunggu' => $jumlahMenunggu,
            'cari'           => $cari,
            'pegawai'        => $pegawai,
            'seksiKasi'      => $seksiKasi,
        ]);
    }

    // =========================================================================
    // edit() & update() — UC-07 Tahap 2
    // =========================================================================

    /**
     * Form Penempatan — GET /kepala-seksi/penempatan/{peserta}/edit
     *
     * Tampilkan detail peserta dan daftar mentor yang bisa dipilih.
     * Guard: peserta belum ditempatkan, status Belum Mulai, keputusan Diterima.
     */
    public function edit(Request $request, PesertaMagang $peserta): View
    {
        // ── Guard: KaSi harus terdaftar sebagai pegawai dengan seksi ────────
        $pegawai = $this->pegawaiLogin();
        abort_if($pegawai === null, 403, 'Akun Anda tidak terdaftar sebagai pegawai.');
        abort_if($pegawai->id_seksi === null, 403,
            'Akun Anda belum memiliki seksi. Hubungi Superadmin untuk menetapkan seksi Anda.');

        // ── Eager load relasi yang dibutuhkan ────────────────────────────────
        $peserta->load([
            'keputusan.calon:id_calon,id_pengajuan,nama_lengkap,email,no_hp,institusi_asal,jurusan_prodi,nomor_induk_pendidikan',
            'keputusan.calon.pengajuan:id_pengajuan,kode_pengajuan,jalur,nama_institusi,diajukan_oleh',
            'keputusan.calon.pengajuan.diajukanOleh:id_pengguna,nama_lengkap',
            'pengguna:id_pengguna,nama_lengkap,email,status_akun',
        ]);

        // ── Guard: hanya peserta yang belum ditempatkan ──────────────────────
        abort_if(
            $peserta->id_seksi !== null || $peserta->id_mentor !== null,
            403,
            'Peserta ini sudah ditempatkan dan tidak dapat diubah melalui halaman ini.'
        );

        // ── Guard: status harus Belum Mulai ──────────────────────────────────
        abort_if(
            $peserta->status_magang !== 'Belum Mulai',
            403,
            'Penempatan hanya dapat dilakukan pada peserta dengan status Belum Mulai.'
        );

        // ── Guard: keputusan penerimaan harus Diterima ───────────────────────
        abort_if(
            $peserta->keputusan?->keputusan !== 'Diterima',
            403,
            'Peserta ini tidak memiliki keputusan penerimaan yang sah.'
        );

        // ── Guard: akun peserta harus aktif ──────────────────────────────────
        abort_if(
            ! $peserta->pengguna?->isAktif(),
            403,
            'Akun peserta tidak aktif. Pastikan akun peserta sudah diaktifkan oleh Superadmin.'
        );

        // ── Daftar mentor: peran MENTOR, seksi KaSi, akun aktif ─────────────
        $daftarMentor = Pegawai::where('id_seksi', $pegawai->id_seksi)
            ->whereHas('pengguna', fn (Builder $q) => $q
                ->where('status_akun', 'Aktif')
                ->whereHas('peran', fn (Builder $p) => $p
                    ->where('kode_peran', KodePeran::MENTOR)
                )
            )
            ->with('pengguna:id_pengguna,nama_lengkap,email')
            ->get();

        $seksiKasi = Seksi::find($pegawai->id_seksi);

        return view('kepala-seksi.penempatan.edit', compact(
            'peserta',
            'daftarMentor',
            'pegawai',
            'seksiKasi',
        ));
    }

    /**
     * Simpan Penempatan — PUT /kepala-seksi/penempatan/{peserta}
     *
     * Simpan id_seksi, id_mentor, ditetapkan_oleh, tanggal_penempatan.
     * Validasi ulang di dalam transaksi dengan lockForUpdate().
     * TIDAK mengubah tanggal_mulai, tanggal_selesai, maupun status_magang.
     */
    public function update(Request $request, PesertaMagang $peserta): RedirectResponse
    {
        // ── Guard: KaSi harus terdaftar sebagai pegawai dengan seksi ────────
        $pegawai = $this->pegawaiLogin();
        abort_if($pegawai === null, 403, 'Akun Anda tidak terdaftar sebagai pegawai.');
        abort_if($pegawai->id_seksi === null, 403,
            'Akun Anda belum memiliki seksi. Hubungi Superadmin untuk menetapkan seksi Anda.');

        // ── Pre-check sebelum transaksi ──────────────────────────────────────
        if ($peserta->id_seksi !== null || $peserta->id_mentor !== null) {
            return redirect()
                ->route('kasi.penempatan.index', ['tab' => 'riwayat'])
                ->with('gagal', 'Peserta ini sudah ditempatkan.');
        }

        // ── Validasi input ───────────────────────────────────────────────────
        $data = $request->validate(
            ['id_mentor' => ['required', 'integer', 'exists:pegawai,id_pegawai']],
            [
                'id_mentor.required' => 'Mentor wajib dipilih.',
                'id_mentor.integer'  => 'Data mentor tidak valid.',
                'id_mentor.exists'   => 'Mentor yang dipilih tidak ditemukan.',
            ]
        );

        // ── Transaksi dengan lockForUpdate() ─────────────────────────────────
        $hasil = DB::transaction(function () use ($peserta, $pegawai, $data) {

            /** @var PesertaMagang $terkunci */
            $terkunci = PesertaMagang::whereKey($peserta->id_peserta_magang)
                ->lockForUpdate()
                ->firstOrFail();

            // Guard final: belum ditempatkan
            if ($terkunci->id_seksi !== null || $terkunci->id_mentor !== null) {
                return ['kode' => 'sudah_ditempatkan'];
            }

            // Guard final: status masih Belum Mulai
            if ($terkunci->status_magang !== 'Belum Mulai') {
                return ['kode' => 'status_tidak_valid'];
            }

            // Guard final: keputusan masih Diterima
            $terkunci->load('keputusan', 'pengguna');
            if ($terkunci->keputusan?->keputusan !== 'Diterima') {
                return ['kode' => 'keputusan_tidak_valid'];
            }

            // Guard final: akun peserta masih aktif
            if (! $terkunci->pengguna?->isAktif()) {
                return ['kode' => 'akun_peserta_nonaktif'];
            }

            // Validasi mentor: harus dari seksi KaSi, peran MENTOR, akun aktif
            $mentor = Pegawai::where('id_pegawai', $data['id_mentor'])
                ->where('id_seksi', $pegawai->id_seksi)
                ->whereHas('pengguna', fn (Builder $q) => $q
                    ->where('status_akun', 'Aktif')
                    ->whereHas('peran', fn (Builder $p) => $p
                        ->where('kode_peran', KodePeran::MENTOR)
                    )
                )
                ->with('pengguna:id_pengguna,id_pengguna,nama_lengkap')
                ->first();

            if (! $mentor) {
                return ['kode' => 'mentor_tidak_valid'];
            }

            // Simpan penempatan (TIDAK menyentuh tanggal_mulai, tanggal_selesai, status_magang)
            $terkunci->update([
                'id_seksi'           => $pegawai->id_seksi,
                'id_mentor'          => $mentor->id_pegawai,
                'ditetapkan_oleh'    => $pegawai->id_pegawai,
                'tanggal_penempatan' => today(),
            ]);

            return [
                'kode'               => 'sukses',
                'nama_peserta'       => $terkunci->pengguna->nama_lengkap,
                'id_pengguna_peserta' => $terkunci->id_pengguna,
                'id_pengguna_mentor' => $mentor->pengguna?->id_pengguna,
                'nama_mentor'        => $mentor->pengguna?->nama_lengkap ?? '—',
            ];
        });

        // ── Tangani hasil transaksi ──────────────────────────────────────────
        $kode = $hasil['kode'] ?? 'error';

        if ($kode !== 'sukses') {
            $pesanGagal = match ($kode) {
                'sudah_ditempatkan'   => 'Peserta ini sudah ditempatkan oleh pengguna lain.',
                'status_tidak_valid'  => 'Status peserta sudah berubah. Penempatan tidak dapat dilakukan.',
                'keputusan_tidak_valid' => 'Keputusan penerimaan peserta sudah berubah.',
                'akun_peserta_nonaktif' => 'Akun peserta tidak lagi aktif.',
                'mentor_tidak_valid'  => 'Mentor yang dipilih tidak valid atau bukan dari seksi Anda.',
                default               => 'Penempatan gagal. Silakan coba kembali.',
            };

            if ($kode === 'sudah_ditempatkan') {
                return redirect()
                    ->route('kasi.penempatan.index', ['tab' => 'riwayat'])
                    ->with('gagal', $pesanGagal);
            }

            if ($kode === 'mentor_tidak_valid') {
                return back()->withInput()->with('gagal', $pesanGagal);
            }

            return redirect()
                ->route('kasi.penempatan.index')
                ->with('gagal', $pesanGagal);
        }

        // ── Log aktivitas ────────────────────────────────────────────────────
        Log::channel('aktivitas')->info('peserta_ditempatkan', [
            'id_peserta_magang'  => $peserta->id_peserta_magang,
            'nama_peserta'       => $hasil['nama_peserta'],
            'id_mentor'          => $data['id_mentor'],
            'nama_mentor'        => $hasil['nama_mentor'],
            'id_seksi'           => $pegawai->id_seksi,
            'ditetapkan_oleh'    => $pegawai->id_pegawai,
            'tanggal_penempatan' => today()->toDateString(),
        ]);

        // ── Notifikasi (di luar transaksi) ───────────────────────────────────
        $idPesertaMagang = $peserta->id_peserta_magang;

        try {
            Notifikasi::kirim(
                $hasil['id_pengguna_peserta'],
                'PESERTA_DITEMPATKAN',
                "Selamat! Anda telah ditempatkan di magang DTI dan akan dibimbing oleh {$hasil['nama_mentor']}.",
                'peserta_magang',
                $idPesertaMagang
            );
        } catch (\Throwable) {
            // Kegagalan notifikasi tidak membatalkan penempatan
        }

        if ($hasil['id_pengguna_mentor']) {
            try {
                Notifikasi::kirim(
                    $hasil['id_pengguna_mentor'],
                    'PESERTA_DITEMPATKAN',
                    "Anda ditunjuk sebagai mentor untuk peserta {$hasil['nama_peserta']}.",
                    'peserta_magang',
                    $idPesertaMagang
                );
            } catch (\Throwable) {
                // Kegagalan notifikasi tidak membatalkan penempatan
            }
        }

        return redirect()
            ->route('kasi.penempatan.index', ['tab' => 'riwayat'])
            ->with('sukses', "Peserta {$hasil['nama_peserta']} berhasil ditempatkan dengan mentor {$hasil['nama_mentor']}.");
    }
}
