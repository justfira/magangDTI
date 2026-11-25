<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\KeputusanPenerimaan;
use App\Models\Pegawai;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\PesertaMagang;
use App\Models\Seksi;
use App\Support\Notifikasi;
use App\Support\Peran as KodePeran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Manajemen Akun & Hak Akses (UC-17)
 * Aktor  : Superadmin
 * Tabel  : pengguna, pegawai, peran, peserta_magang
 *
 * Hak akses ditentukan oleh PERAN akun (RBAC): Superadmin, Mentor, Kepala Seksi, Direktur, Peserta.
 * Hanya Superadmin yang boleh membuat akun pegawai, mengubah peran (hak akses), dan menonaktifkan akun.
 *
 * Status : akun pegawai SELESAI. Aktivasi peserta diterima (pesertaDiterima & aktivasiPeserta) masih kerangka.
 */
class AkunController extends Controller
{
    /** Peran yang boleh diberikan ke akun pegawai, beserta tabel yang butuh seksi. */
    private const PERLU_SEKSI = [KodePeran::MENTOR, KodePeran::KEPALA_SEKSI];

    /** Daftar akun beserta peran & status, dengan filter peran/status/kata kunci. */
    public function index(Request $request): View
    {
        $akun = Pengguna::with(['peran', 'pegawai.seksi'])
            ->when($request->filled('peran'), fn ($q) => $q->whereHas('peran', fn ($p) => $p->where('kode_peran', $request->input('peran'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status_akun', $request->input('status')))
            ->when($request->filled('cari'), function ($q) use ($request) {
                $kata = '%'.$request->input('cari').'%';
                $q->where(fn ($w) => $w->where('nama_lengkap', 'like', $kata)->orWhere('email', 'like', $kata));
            })
            ->orderBy('id_peran')->orderBy('nama_lengkap')
            ->paginate(15)->withQueryString();

        return view('superadmin.akun.index', [
            'akun' => $akun,
            'daftarPeran' => Peran::orderBy('id_peran')->get(),
        ]);
    }

    /** Form tambah akun pegawai. */
    public function create(): View
    {
        return view('superadmin.akun.form', $this->dataForm(new Pengguna(['status_akun' => 'Aktif'])));
    }

    /** Validasi, cek email unik, simpan pengguna (password di-hash) + pegawai dalam satu transaksi. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->aturan($request), $this->pesan());

        $pengguna = DB::transaction(function () use ($data) {
            $pengguna = Pengguna::create([
                'id_peran' => $this->idPeran($data['kode_peran']),
                'nama_lengkap' => $data['nama_lengkap'],
                'email' => $data['email'],
                'password_hash' => Hash::make($data['password']),
                'status_akun' => 'Aktif',
            ]);
            Pegawai::create($this->dataPegawai($data) + ['id_pengguna' => $pengguna->id_pengguna]);

            return $pengguna;
        });
        Log::channel('aktivitas')->info('akun_dibuat', ['id_pengguna' => $pengguna->id_pengguna, 'peran' => $data['kode_peran'], 'oleh' => auth()->id()]);

        return redirect()->route('superadmin.akun.index')->with('sukses', 'Akun berhasil dibuat.');
    }

    /** Form ubah akun pegawai (termasuk mengganti peran/hak akses). */
    public function edit(Pengguna $pengguna): View
    {
        $this->pastikanAkunPegawai($pengguna);

        return view('superadmin.akun.form', $this->dataForm($pengguna->load('peran', 'pegawai')));
    }

    /** Simpan perubahan data, peran (hak akses), dan kata sandi (opsional). */
    public function update(Request $request, Pengguna $pengguna): RedirectResponse
    {
        $this->pastikanAkunPegawai($pengguna);
        $data = $request->validate($this->aturan($request, $pengguna), $this->pesan());

        $kodeLama = $pengguna->kodePeran();
        $kodeBaru = $data['kode_peran'];

        if ($kodeLama !== $kodeBaru) {
            if ($pengguna->is(auth()->user())) {
                return back()->withInput()->withErrors(['kode_peran' => 'Anda tidak dapat mengubah peran akun Anda sendiri.']);
            }
            if ($kodeLama === KodePeran::SUPERADMIN && $this->superadminAktifLain($pengguna) === 0) {
                return back()->withInput()->withErrors(['kode_peran' => 'Peran tidak dapat diubah: ini satu-satunya Superadmin aktif.']);
            }
            if ($kodeLama === KodePeran::MENTOR && $pengguna->pegawai?->pesertaBimbingan()->exists()) {
                return back()->withInput()->withErrors(['kode_peran' => 'Peran tidak dapat diubah: mentor ini masih membimbing peserta. Pindahkan bimbingannya lebih dulu.']);
            }
        }

        DB::transaction(function () use ($data, $pengguna) {
            $perubahan = [
                'id_peran' => $this->idPeran($data['kode_peran']),
                'nama_lengkap' => $data['nama_lengkap'],
                'email' => $data['email'],
            ];
            if (! empty($data['password'])) {
                $perubahan['password_hash'] = Hash::make($data['password']);
            }
            $pengguna->update($perubahan);
            Pegawai::updateOrCreate(['id_pengguna' => $pengguna->id_pengguna], $this->dataPegawai($data));
        });
        Log::channel('aktivitas')->info('akun_diubah', [
            'id_pengguna' => $pengguna->id_pengguna, 'peran_lama' => $kodeLama, 'peran_baru' => $kodeBaru,
            'password_diganti' => ! empty($data['password']), 'oleh' => auth()->id(),
        ]);

        return redirect()->route('superadmin.akun.index')->with('sukses', 'Perubahan akun berhasil disimpan.');
    }

    /** status_akun = Nonaktif. Akun nonaktif tidak bisa login, dan sesi yang sedang berjalan diakhiri (PastikanAkunAktif). */
    public function nonaktifkan(Pengguna $pengguna): RedirectResponse
    {
        if ($pengguna->is(auth()->user())) {
            return back()->with('gagal', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }
        if ($pengguna->punyaPeran(KodePeran::SUPERADMIN) && $pengguna->isAktif() && $this->superadminAktifLain($pengguna) === 0) {
            return back()->with('gagal', 'Tidak dapat menonaktifkan satu-satunya Superadmin aktif.');
        }

        $pengguna->update(['status_akun' => 'Nonaktif']);
        Log::channel('aktivitas')->info('akun_dinonaktifkan', ['id_pengguna' => $pengguna->id_pengguna, 'oleh' => auth()->id()]);

        return back()->with('sukses', 'Akun berhasil dinonaktifkan.');
    }

    /** Aktifkan kembali akun yang dinonaktifkan. */
    public function aktifkan(Pengguna $pengguna): RedirectResponse
    {
        $pengguna->update(['status_akun' => 'Aktif']);
        Log::channel('aktivitas')->info('akun_diaktifkan', ['id_pengguna' => $pengguna->id_pengguna, 'oleh' => auth()->id()]);

        return back()->with('sukses', 'Akun berhasil diaktifkan kembali.');
    }

    /** Matriks hak akses: menu/fitur yang didapat tiap peran (hanya baca; peran diubah lewat Ubah Akun). */
    public function hakAkses(): View
    {
        $peran = Peran::withCount([
            'pengguna as jumlah_aktif' => fn ($q) => $q->where('status_akun', 'Aktif'),
            'pengguna as jumlah_nonaktif' => fn ($q) => $q->where('status_akun', '!=', 'Aktif'),
        ])->orderBy('id_peran')->get();

        $menuPeran = [];
        foreach ($peran as $p) {
            $menuPeran[$p->kode_peran] = collect(config('menu.'.$p->kode_peran, []))->pluck('label')->all();
        }

        return view('superadmin.akun.hak-akses', compact('peran', 'menuPeran'));
    }

    // =========================================================================
    // UC-06 — Aktivasi Peserta Magang
    // =========================================================================

    private const TAB_PESERTA = [
        'menunggu' => 'Menunggu Aktivasi',
        'riwayat'  => 'Sudah Diaktifkan',
    ];

    /**
     * Daftar Peserta Diterima — GET /superadmin/akun/peserta-diterima
     *
     * Tab "Menunggu": keputusan = Diterima, belum punya peserta_magang.
     * Tab "Riwayat" : sudah punya peserta_magang.
     */
    public function pesertaDiterima(Request $request): View
    {
        $tab  = array_key_exists($request->query('tab'), self::TAB_PESERTA)
              ? $request->query('tab')
              : 'menunggu';

        $cari = $request->string('cari')->trim()->value();

        $keputusan = KeputusanPenerimaan::with([
                'calon:id_calon,id_pengajuan,nama_lengkap,email,nomor_induk_pendidikan,institusi_asal,jurusan_prodi',
                'calon.pengajuan:id_pengajuan,kode_pengajuan,jalur,nama_institusi,diajukan_oleh,waktu_pemeriksaan',
                'calon.pengajuan.diajukanOleh:id_pengguna,nama_lengkap,email',
                'pesertaMagang:id_peserta_magang,id_keputusan,id_pengguna,tanggal_mulai,tanggal_selesai,status_magang,created_at',
                'pesertaMagang.pengguna:id_pengguna,nama_lengkap,email,status_akun',
            ])
            ->where('keputusan', 'Diterima')
            ->when($tab === 'menunggu', fn (Builder $q) =>
                $q->doesntHave('pesertaMagang')
                  ->orderBy('waktu_keputusan')
            )
            ->when($tab === 'riwayat', fn (Builder $q) =>
                $q->has('pesertaMagang')
                  ->orderByDesc('waktu_keputusan')
            )
            ->when($cari !== '', fn (Builder $q) =>
                $q->whereHas('calon', fn (Builder $cq) =>
                    $cq->where('nama_lengkap', 'like', "%{$cari}%")
                       ->orWhere('email', 'like', "%{$cari}%")
                       ->orWhereHas('pengajuan', fn (Builder $pq) =>
                           $pq->where('nama_institusi', 'like', "%{$cari}%")
                              ->orWhere('kode_pengajuan', 'like', "%{$cari}%")
                       )
                )
            )
            ->paginate(15)
            ->withQueryString();

        $jumlahMenunggu = KeputusanPenerimaan::where('keputusan', 'Diterima')
            ->doesntHave('pesertaMagang')
            ->count();

        return view('superadmin.akun.peserta-diterima', [
            'keputusan'      => $keputusan,
            'tab'            => $tab,
            'daftarTab'      => self::TAB_PESERTA,
            'jumlahMenunggu' => $jumlahMenunggu,
            'cari'           => $cari,
        ]);
    }

    /**
     * Aktifkan Peserta Magang — POST /superadmin/akun/peserta-diterima/{keputusan}
     *
     * Alur:
     *  1. Guard: keputusan = Diterima.
     *  2. Guard (pre-check): belum punya peserta_magang.
     *  3. Validasi tanggal mulai dan selesai.
     *  4. DB::transaction + lockForUpdate (cegah double-submit):
     *     a. DTI  → ambil akun dari pengajuan.diajukan_oleh, pastikan peran PESERTA.
     *     b. INSTITUSI → cari email di pengguna; jika tidak ada, buat akun baru peran PESERTA.
     *     c. Buat peserta_magang.
     *  5. Log aktivitas.
     *  6. Notifikasi lonceng ke peserta (di luar transaksi).
     *  7. Redirect dengan flash sukses.
     *
     * Catatan password INSTITUSI:
     *  - Password acak 16 karakter di-hash dengan Hash::make().
     *  - Ditampilkan SEKALI di flash session superadmin (bukan di log, bukan di URL).
     *  - Superadmin menyampaikan ke peserta secara langsung/offline.
     *  - Tidak ada mekanisme email di proyek ini.
     */
    public function aktivasiPeserta(Request $request, KeputusanPenerimaan $keputusan): RedirectResponse
    {
        // ── A1: keputusan harus Diterima ──────────────────────────────────
        abort_if($keputusan->keputusan !== 'Diterima', 403,
            'Hanya peserta dengan keputusan Diterima yang dapat diaktifkan.');

        // ── A2: pre-check duplikasi (sebelum validasi form) ───────────────
        if ($keputusan->pesertaMagang()->exists()) {
            return redirect()
                ->route('superadmin.akun.diterima', ['tab' => 'riwayat'])
                ->with('gagal', 'Peserta ini sudah pernah diaktifkan sebelumnya.');
        }

        // ── Validasi form tanggal ─────────────────────────────────────────
        $data = $request->validate([
            'tanggal_mulai'   => ['required', 'date', 'after_or_equal:today'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ], [
            'tanggal_mulai.required'          => 'Tanggal mulai magang wajib diisi.',
            'tanggal_mulai.date'              => 'Format tanggal mulai tidak valid.',
            'tanggal_mulai.after_or_equal'    => 'Tanggal mulai tidak boleh di masa lalu.',
            'tanggal_selesai.required'        => 'Tanggal selesai magang wajib diisi.',
            'tanggal_selesai.date'            => 'Format tanggal selesai tidak valid.',
            'tanggal_selesai.after_or_equal'  => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

        // ── Load relasi calon + pengajuan ─────────────────────────────────
        $keputusan->load([
            'calon:id_calon,id_pengajuan,nama_lengkap,email',
            'calon.pengajuan:id_pengajuan,jalur,diajukan_oleh',
        ]);

        $calon   = $keputusan->calon;
        $jalur   = $calon?->pengajuan?->jalur;
        $nama    = $calon?->nama_lengkap ?? "Calon #{$keputusan->id_calon}";

        // ── Transaksi dengan row-level lock ───────────────────────────────
        $hasilAktivasi = null;

        try {
            $hasilAktivasi = DB::transaction(function () use ($keputusan, $calon, $jalur, $data) {

                // Lock baris keputusan — cegah dua request bersamaan.
                $terkunci = KeputusanPenerimaan::whereKey($keputusan->id_keputusan)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Guard final di dalam transaksi (setelah lock).
                if ($terkunci->pesertaMagang()->exists()) {
                    return ['status' => 'duplikat'];
                }

                // ── Tentukan akun peserta ──────────────────────────────
                $idPengguna     = null;
                $passwordBaru   = null;    // null = pakai akun lama
                $akunBaru       = false;

                if ($jalur === 'DTI') {
                    // Ambil akun pendaftar dari pengajuan.diajukan_oleh
                    $idPendaftar = $calon?->pengajuan?->diajukan_oleh;
                    if (! $idPendaftar) {
                        return ['status' => 'error', 'pesan' => 'Akun pendaftar DTI tidak ditemukan pada pengajuan ini.'];
                    }

                    $akunPendaftar = Pengguna::with('peran')->find($idPendaftar);
                    if (! $akunPendaftar) {
                        return ['status' => 'error', 'pesan' => 'Akun pendaftar DTI tidak ditemukan di database.'];
                    }
                    if (! $akunPendaftar->isAktif()) {
                        return ['status' => 'error', 'pesan' => "Akun pendaftar DTI ({$akunPendaftar->email}) berstatus nonaktif. Aktifkan akun terlebih dahulu."];
                    }
                    if ($akunPendaftar->kodePeran() !== KodePeran::PESERTA) {
                        return ['status' => 'error', 'pesan' => "Akun pendaftar DTI ({$akunPendaftar->email}) memiliki peran {$akunPendaftar->kodePeran()}, bukan PESERTA. Hubungi administrator."];
                    }

                    $idPengguna = $akunPendaftar->id_pengguna;

                } else {
                    // Jalur INSTITUSI — cari atau buat akun berdasarkan email calon
                    $emailCalon = $calon?->email;
                    if (! $emailCalon) {
                        return ['status' => 'error', 'pesan' => 'Email calon peserta institusi tidak ditemukan.'];
                    }

                    $akunLama = Pengguna::with('peran')->where('email', $emailCalon)->first();

                    if ($akunLama) {
                        // Email sudah terdaftar — hanya boleh lanjut jika peran PESERTA
                        if ($akunLama->kodePeran() !== KodePeran::PESERTA) {
                            return [
                                'status' => 'error',
                                'pesan'  => "Email {$emailCalon} sudah terdaftar dengan peran {$akunLama->kodePeran()}. "
                                          . 'Aktivasi tidak dapat dilanjutkan otomatis. Hubungi administrator.',
                            ];
                        }
                        $idPengguna = $akunLama->id_pengguna;

                    } else {
                        // Buat akun baru peran PESERTA
                        $idPeranPeserta = Peran::where('kode_peran', KodePeran::PESERTA)->value('id_peran');
                        if (! $idPeranPeserta) {
                            return ['status' => 'error', 'pesan' => 'Konfigurasi peran PESERTA tidak ditemukan. Hubungi administrator.'];
                        }

                        // Password acak 16 karakter (tidak tampil di log)
                        $passwordBaru = Str::password(16, letters: true, numbers: true, symbols: false);

                        $akunBaru = Pengguna::create([
                            'id_peran'      => $idPeranPeserta,
                            'nama_lengkap'  => $calon->nama_lengkap,
                            'email'         => $emailCalon,
                            'password_hash' => Hash::make($passwordBaru),
                            'status_akun'   => 'Aktif',
                        ]);

                        $idPengguna  = $akunBaru->id_pengguna;
                        $akunBaru    = true;   // flag: akun baru dibuat
                    }
                }

                // ── Buat record peserta_magang ─────────────────────────
                $peserta = PesertaMagang::create([
                    'id_keputusan'      => $keputusan->id_keputusan,
                    'id_pengguna'       => $idPengguna,
                    'tanggal_mulai'     => $data['tanggal_mulai'],
                    'tanggal_selesai'   => $data['tanggal_selesai'],
                    'status_magang'     => 'Belum Mulai',
                    // id_seksi, id_mentor, ditetapkan_oleh, tanggal_penempatan → NULL (diisi UC-07)
                ]);

                return [
                    'status'        => 'sukses',
                    'id_pengguna'   => $idPengguna,
                    'id_peserta'    => $peserta->id_peserta_magang,
                    'password_baru' => $passwordBaru,   // null jika pakai akun lama
                    'akun_baru'     => $akunBaru,
                ];
            });

        } catch (\Throwable $e) {
            Log::error('aktivasi_peserta_gagal', [
                'id_keputusan' => $keputusan->id_keputusan,
                'error'        => $e->getMessage(),
                'oleh'         => auth()->id(),
            ]);
            return back()->withInput()
                ->with('gagal', 'Aktivasi gagal karena kesalahan sistem. Silakan coba lagi atau hubungi administrator.');
        }

        // ── Tangani hasil transaksi ───────────────────────────────────────
        if (($hasilAktivasi['status'] ?? '') === 'duplikat') {
            return redirect()
                ->route('superadmin.akun.diterima', ['tab' => 'riwayat'])
                ->with('gagal', 'Peserta ini sudah diaktifkan oleh sesi lain.');
        }

        if (($hasilAktivasi['status'] ?? '') === 'error') {
            return back()->withInput()
                ->with('gagal', $hasilAktivasi['pesan'] ?? 'Aktivasi gagal.');
        }

        // ── Log aktivitas ─────────────────────────────────────────────────
        Log::channel('aktivitas')->info('peserta_diaktifkan', [
            'id_keputusan'    => $keputusan->id_keputusan,
            'id_calon'        => $keputusan->id_calon,
            'id_pengguna'     => $hasilAktivasi['id_pengguna'],
            'id_peserta'      => $hasilAktivasi['id_peserta'],
            'jalur'           => $jalur,
            'akun_baru'       => $hasilAktivasi['akun_baru'],
            'oleh'            => auth()->id(),
        ]);

        // ── Notifikasi ke peserta (di luar transaksi — kegagalan tidak rollback) ──
        try {
            Notifikasi::kirim(
                $hasilAktivasi['id_pengguna'],
                'PESERTA_DIAKTIFKAN',
                "Selamat! Akun peserta magang Anda telah diaktifkan oleh Superadmin. "
                    . "Masa magang: {$data['tanggal_mulai']} s.d. {$data['tanggal_selesai']}. "
                    . 'Tunggu informasi penempatan dari Kepala Seksi.',
                'peserta_magang',
                $hasilAktivasi['id_peserta'],
            );
        } catch (\Throwable) { /* notifikasi gagal tidak memblokir */ }

        // ── Flash sukses (password sementara hanya untuk INSTITUSI akun baru) ──
        $pesanSukses = "Peserta {$nama} berhasil diaktifkan.";

        if ($hasilAktivasi['akun_baru'] && $hasilAktivasi['password_baru']) {
            // Password ditampilkan SEKALI di sini lalu hilang — tidak ada cara lain
            // untuk mendapatkannya kembali. Superadmin wajib menyampaikan ke peserta.
            $pesanSukses .= " Akun baru dibuat. "
                . "Email: {$calon?->email} | "
                . "Password sementara: <code>{$hasilAktivasi['password_baru']}</code> "
                . "(sampaikan kepada peserta; password tidak dapat dilihat kembali).";
        }

        return redirect()
            ->route('superadmin.akun.diterima', ['tab' => 'riwayat'])
            ->with('sukses', $pesanSukses);
    }

    // ---------------------------------------------------------------- pembantu

    /** Ubah akun hanya untuk akun pegawai; akun peserta cukup diaktifkan/dinonaktifkan. */
    private function pastikanAkunPegawai(Pengguna $pengguna): void
    {
        abort_unless($pengguna->punyaPeran(...KodePeran::PEGAWAI), 404);
    }

    private function aturan(Request $request, ?Pengguna $pengguna = null): array
    {
        $perluSeksi = in_array($request->input('kode_peran'), self::PERLU_SEKSI, true);

        return [
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:191', Rule::unique('pengguna', 'email')->ignore($pengguna?->id_pengguna, 'id_pengguna')],
            'password' => [$pengguna ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'kode_peran' => ['required', Rule::in(KodePeran::PEGAWAI)],
            'id_seksi' => [$perluSeksi ? 'required' : 'nullable', 'exists:seksi,id_seksi'],
            'nip' => ['nullable', 'string', 'max:30', Rule::unique('pegawai', 'nip')->ignore($pengguna?->pegawai?->id_pegawai, 'id_pegawai')],
            'jabatan' => ['nullable', 'string', 'max:60'],
        ];
    }

    private function pesan(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'nip.unique' => 'NIP sudah terdaftar.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
            'kode_peran.in' => 'Peran tidak valid.',
            'id_seksi.required' => 'Seksi wajib dipilih untuk Mentor dan Kepala Seksi.',
        ];
    }

    /** Data tabel pegawai; seksi hanya disimpan untuk peran yang butuh seksi. */
    private function dataPegawai(array $data): array
    {
        return [
            'id_seksi' => in_array($data['kode_peran'], self::PERLU_SEKSI, true) ? $data['id_seksi'] : null,
            'nip' => $data['nip'] ?? null,
            'jabatan' => $data['jabatan'] ?? null,
        ];
    }

    private function idPeran(string $kode): int
    {
        return (int) Peran::where('kode_peran', $kode)->value('id_peran');
    }

    private function superadminAktifLain(Pengguna $pengguna): int
    {
        return Pengguna::whereHas('peran', fn ($q) => $q->where('kode_peran', KodePeran::SUPERADMIN))
            ->where('status_akun', 'Aktif')
            ->where('id_pengguna', '!=', $pengguna->id_pengguna)
            ->count();
    }

    private function dataForm(Pengguna $pengguna): array
    {
        return [
            'pengguna' => $pengguna,
            'peranPegawai' => Peran::whereIn('kode_peran', KodePeran::PEGAWAI)->orderBy('id_peran')->get(),
            'seksi' => Seksi::orderBy('nama_seksi')->get(),
            'perluSeksi' => self::PERLU_SEKSI,
            'akunSendiri' => $pengguna->exists && $pengguna->is(auth()->user()),
        ];
    }
}
