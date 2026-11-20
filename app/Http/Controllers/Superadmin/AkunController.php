<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\KeputusanPenerimaan;
use App\Models\Pegawai;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Seksi;
use App\Support\Peran as KodePeran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

    /**
     * Aktivasi Peserta Diterima — GET /superadmin/akun/peserta-diterima
     * Langkah:
     *  - Daftar calon dengan keputusan Diterima yang belum punya peserta_magang (A3).
     */
    public function pesertaDiterima(Request $request): View
    {
        return $this->halamanRancangan(
            'Aktivasi Peserta Diterima', 'UC-17', 'Superadmin',
            ['Daftar calon dengan keputusan Diterima yang belum punya peserta_magang (A3).'],
            ['pengguna', 'pegawai', 'peserta_magang'],
        );
    }

    /**
     * Aktifkan Peserta Magang — POST /superadmin/akun/peserta-diterima/{keputusan}
     * Langkah:
     *  - Cek ulang keputusan = Diterima (A3).
     *  - Jalur INSTITUSI: buat akun pengguna (peran PESERTA) dan kirim tautan buat kata sandi. Jalur DTI: pakai akun pendaftar.
     *  - Buat peserta_magang (id_keputusan, id_pengguna, tanggal_mulai, tanggal_selesai, status Belum Mulai/Aktif).
     */
    public function aktivasiPeserta(Request $request, KeputusanPenerimaan $keputusan): RedirectResponse
    {
        // TODO: validasi + simpan sesuai langkah di atas (gunakan DB::transaction bila menyentuh >1 tabel)
        return $this->belumDiimplementasikan('Aktifkan Peserta Magang', 'UC-17');
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
