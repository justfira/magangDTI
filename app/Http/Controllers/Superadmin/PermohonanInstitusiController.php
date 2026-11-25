<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\CalonPeserta;
use App\Models\DokumenPengajuan;
use App\Models\Pengajuan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * Permohonan Institusi (UC-04)
 * Aktor  : Superadmin
 * Tabel  : pengajuan, calon_peserta, dokumen_pengajuan
 */
class PermohonanInstitusiController extends Controller
{
    private const DISK   = 'local';
    private const FOLDER = 'surat-institusi';

    // =========================================================================
    // index() — Daftar permohonan institusi
    // =========================================================================

    public function index(Request $request): View
    {
        $cari   = $request->string('cari')->trim()->value();
        $status = $request->input('status');

        $statusValid = in_array($status, Pengajuan::STATUS, true) ? $status : null;

        $permohonan = Pengajuan::where('jalur', 'INSTITUSI')
            ->with([
                'dicatatOleh.pengguna:id_pengguna,nama_lengkap',
                'pemeriksa.pengguna:id_pengguna,nama_lengkap',
            ])
            ->withCount('calonPeserta')
            ->when($statusValid, fn (Builder $q, string $s) => $q->where('status_pengajuan', $s))
            ->when($cari !== '', fn (Builder $q) => $q->where(fn (Builder $qq) =>
                $qq->where('kode_pengajuan',  'like', "%{$cari}%")
                   ->orWhere('nama_institusi', 'like', "%{$cari}%")
                   ->orWhere('nomor_surat',    'like', "%{$cari}%")
            ))
            ->orderByDesc('tanggal_pengajuan')
            ->paginate(15)
            ->withQueryString();

        $jumlahBaru = Pengajuan::where('jalur', 'INSTITUSI')
            ->where('status_pengajuan', 'Diajukan')
            ->count();

        return view('superadmin.permohonan.index', [
            'permohonan'   => $permohonan,
            'daftarStatus' => Pengajuan::STATUS,
            'jumlahBaru'   => $jumlahBaru,
            'cari'         => $cari,
            'statusAktif'  => $statusValid,
        ]);
    }

    // =========================================================================
    // create() — Form tambah permohonan
    // =========================================================================

    public function create(Request $request): View
    {
        return view('superadmin.permohonan.create');
    }

    // =========================================================================
    // store() — Simpan permohonan institusi
    // =========================================================================

    public function store(Request $request): RedirectResponse
    {
        // ── 1. Guard: hanya pegawai terdaftar ─────────────────────────────
        $pegawai = $this->pegawaiLogin();
        abort_if($pegawai === null, 403, 'Akun Anda tidak terdaftar sebagai pegawai.');

        // ── 2. Validasi ────────────────────────────────────────────────────
        $data = $request->validate([
            // Identitas institusi
            'nama_institusi'   => ['required', 'string', 'max:180'],
            'nomor_surat'      => ['required', 'string', 'max:100', 'unique:pengajuan,nomor_surat'],
            'tanggal_surat'    => ['required', 'date', 'before_or_equal:today'],
            'kontak_institusi' => ['nullable', 'string', 'max:150'],

            // Calon peserta (array dinamis)
            'calon'                            => ['required', 'array', 'min:1'],
            'calon.*.nama_lengkap'             => ['required', 'string', 'max:150'],
            'calon.*.email'                    => ['nullable', 'email', 'max:191'],
            'calon.*.no_hp'                    => ['nullable', 'string', 'max:30'],
            'calon.*.nomor_induk_pendidikan'   => ['nullable', 'string', 'max:40'],
            'calon.*.institusi_asal'           => ['nullable', 'string', 'max:180'],
            'calon.*.jurusan_prodi'            => ['nullable', 'string', 'max:150'],

            // Scan surat permohonan
            'surat' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'nama_institusi.required'          => 'Nama institusi wajib diisi.',
            'nama_institusi.max'               => 'Nama institusi maksimal 180 karakter.',
            'nomor_surat.required'             => 'Nomor surat wajib diisi.',
            'nomor_surat.max'                  => 'Nomor surat maksimal 100 karakter.',
            'nomor_surat.unique'               => 'Nomor surat ini sudah terdaftar dalam sistem.',
            'tanggal_surat.required'           => 'Tanggal surat wajib diisi.',
            'tanggal_surat.date'               => 'Format tanggal surat tidak valid.',
            'tanggal_surat.before_or_equal'    => 'Tanggal surat tidak boleh melebihi hari ini.',
            'calon.required'                   => 'Minimal satu calon peserta harus diisi.',
            'calon.min'                        => 'Minimal satu calon peserta harus diisi.',
            'calon.*.nama_lengkap.required'    => 'Nama lengkap calon peserta wajib diisi.',
            'calon.*.nama_lengkap.max'         => 'Nama lengkap maksimal 150 karakter.',
            'calon.*.email.email'              => 'Format email calon peserta tidak valid.',
            'calon.*.email.max'                => 'Email maksimal 191 karakter.',
            'calon.*.no_hp.max'                => 'No. HP maksimal 30 karakter.',
            'calon.*.nomor_induk_pendidikan.max' => 'NIM/NIS maksimal 40 karakter.',
            'calon.*.institusi_asal.max'       => 'Nama institusi asal maksimal 180 karakter.',
            'calon.*.jurusan_prodi.max'        => 'Jurusan/prodi maksimal 150 karakter.',
            'surat.required'                   => 'Scan surat permohonan wajib diunggah.',
            'surat.file'                       => 'File surat gagal diunggah. Silakan coba lagi.',
            'surat.mimes'                      => 'File surat harus berformat PDF, JPG, JPEG, atau PNG.',
            'surat.max'                        => 'Ukuran file surat maksimal 5 MB.',
        ]);

        // ── 3. Upload file (di luar transaksi) ─────────────────────────────
        // File tidak bisa di-rollback bersama DB; upload dulu, hapus bila DB gagal.
        $file     = $request->file('surat');
        $namaAsli = $file->getClientOriginalName();
        $ext      = $file->getClientOriginalExtension();
        $ukuran   = $file->getSize();

        // Nama file dibuat sistem: surat-{uuid}.{ext} — tidak bergantung id_pengajuan
        // agar file sudah ada sebelum INSERT dan tidak perlu update path setelahnya.
        $namaFile = 'surat-' . Str::uuid() . '.' . $ext;
        $pathBaru = $file->storeAs(self::FOLDER, $namaFile, self::DISK);

        if ($pathBaru === false) {
            return back()->withInput()
                ->with('gagal', 'File surat gagal disimpan. Silakan coba lagi.');
        }

        // ── 4. Generate kode & simpan (dalam transaksi) ───────────────────
        $kode = null;
        try {
            DB::transaction(function () use ($data, $pegawai, $pathBaru, $namaAsli, $ukuran, &$kode) {

                // 4a. Generate kode unik INST-YYYY-NNNN
                // Kunci dengan SELECT ... FOR UPDATE agar aman dari concurrent insert.
                $tahun = now()->year;
                $baris = DB::selectOne(
                    "SELECT MAX(CAST(SUBSTRING_INDEX(kode_pengajuan, '-', -1) AS UNSIGNED)) AS maks
                     FROM pengajuan
                     WHERE jalur = 'INSTITUSI'
                       AND kode_pengajuan LIKE ?
                       FOR UPDATE",
                    ["INST-{$tahun}-%"]
                );
                $nomor = (int) ($baris->maks ?? 0) + 1;
                $kode  = sprintf('INST-%d-%04d', $tahun, $nomor);

                // 4b. INSERT pengajuan
                $pengajuan = Pengajuan::create([
                    'id_program'        => null,
                    'diajukan_oleh'     => null,
                    'dicatat_oleh'      => $pegawai->id_pegawai,
                    'kode_pengajuan'    => $kode,
                    'jalur'             => 'INSTITUSI',
                    'nama_institusi'    => $data['nama_institusi'],
                    'nomor_surat'       => $data['nomor_surat'],
                    'tanggal_surat'     => $data['tanggal_surat'],
                    'kontak_institusi'  => $data['kontak_institusi'] ?? null,
                    'status_pengajuan'  => 'Diajukan',
                ]);

                // 4c. INSERT calon_peserta[]
                $barisCalon = array_values(array_filter(
                    $data['calon'],
                    fn (array $c) => trim($c['nama_lengkap'] ?? '') !== ''
                ));
                foreach ($barisCalon as $c) {
                    CalonPeserta::create([
                        'id_pengajuan'           => $pengajuan->id_pengajuan,
                        'nama_lengkap'           => $c['nama_lengkap'],
                        'email'                  => $c['email']                  ?? null,
                        'no_hp'                  => $c['no_hp']                  ?? null,
                        'nomor_induk_pendidikan' => $c['nomor_induk_pendidikan'] ?? null,
                        'institusi_asal'         => $c['institusi_asal']         ?? null,
                        'jurusan_prodi'          => $c['jurusan_prodi']          ?? null,
                    ]);
                }

                // 4d. INSERT dokumen_pengajuan (surat permohonan)
                DokumenPengajuan::create([
                    'id_pengajuan'      => $pengajuan->id_pengajuan,
                    'id_syarat'         => null,
                    'jenis_dokumen'     => 'SURAT_PENGAJUAN',
                    'nama_berkas'       => $namaAsli,
                    'path_berkas'       => $pathBaru,
                    'ukuran_bytes'      => $ukuran,
                ]);
            });

        } catch (UniqueConstraintViolationException) {
            // Kode duplikat (race condition sangat jarang) — hapus file, balik ke form
            Storage::disk(self::DISK)->delete($pathBaru);
            return back()->withInput()
                ->with('gagal', 'Kode pengajuan bentrok. Silakan simpan ulang — sistem akan membuat kode baru.');

        } catch (Throwable $e) {
            // Gagal DB — hapus file agar tidak yatim
            Storage::disk(self::DISK)->delete($pathBaru);
            Log::error('permohonan_institusi_gagal', [
                'error' => $e->getMessage(),
                'oleh'  => auth()->id(),
            ]);
            return back()->withInput()
                ->with('gagal', 'Penyimpanan gagal. Silakan coba lagi atau hubungi administrator.');
        }

        Log::channel('aktivitas')->info('permohonan_institusi_dicatat', [
            'kode_pengajuan' => $kode,
            'oleh'           => auth()->id(),
        ]);

        return redirect()
            ->route('superadmin.permohonan.index')
            ->with('sukses', "Permohonan {$kode} berhasil dicatat.");
    }
}
