<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\ProgramMagang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * UC-02 Mengelola Program Magang (Superadmin) — SELESAI.
 * Dipakai juga sebagai CONTOH POLA untuk modul lain: index -> form -> validasi -> simpan (transaksi) -> notifikasi sukses.
 *
 * Alur: A1 (data/periode tidak valid) ditangani validasi; E1 (gagal simpan, mis. koneksi database) ditangani
 * di method simpan() — data tidak tersimpan dan pesan error ditampilkan.
 */
class ProgramMagangController extends Controller
{
    private const PESAN_SUKSES = 'Program berhasil disimpan.';
    private const PESAN_GAGAL = 'Gagal menyimpan program, silakan coba lagi.';
    private const PESAN_TIDAK_VALID = 'Data belum lengkap atau periode tidak valid.';

    public function index(): View
    {
        $program = ProgramMagang::withCount(['syaratDokumenProgram', 'pengajuan'])->latest('id_program')->paginate(10);

        return view('superadmin.program.index', compact('program'));
    }

    public function create(): View
    {
        return view('superadmin.program.form', ['program' => new ProgramMagang(['status_publikasi' => 'Draft'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $pegawai = $this->pegawaiLogin();
        if (! $pegawai) {
            return back()->withInput()->with('gagal', 'Akun Anda belum terdaftar sebagai pegawai, hubungi Superadmin.');
        }

        return $this->simpan(function () use ($data, $request, $pegawai) {
            DB::transaction(function () use ($data, $request, $pegawai) {
                $program = ProgramMagang::create($data + ['dibuat_oleh' => $pegawai->id_pegawai]);
                $this->simpanSyarat($program, $request->input('syarat_baru'));
            });
            Log::channel('aktivitas')->info('program_dibuat', ['nama' => $data['nama_program'], 'oleh' => auth()->id()]);
        });
    }

    public function edit(ProgramMagang $programMagang): View
    {
        return view('superadmin.program.form', ['program' => $programMagang->load('syaratDokumenProgram')]);
    }

    public function update(Request $request, ProgramMagang $programMagang): RedirectResponse
    {
        $data = $this->validasi($request);

        return $this->simpan(function () use ($data, $request, $programMagang) {
            DB::transaction(function () use ($data, $request, $programMagang) {
                $programMagang->update($data);
                $this->simpanSyarat($programMagang, $request->input('syarat_baru'));
            });
            Log::channel('aktivitas')->info('program_diubah', ['id_program' => $programMagang->id_program, 'oleh' => auth()->id()]);
        });
    }

    /** E1: jika penyimpanan gagal (mis. koneksi database bermasalah), transaksi dibatalkan dan form tetap terisi. */
    private function simpan(callable $aksi): RedirectResponse
    {
        try {
            $aksi();
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('gagal', self::PESAN_GAGAL);
        }

        return redirect()->route('superadmin.program-magang.index')->with('sukses', self::PESAN_SUKSES);
    }

    /** UC-02 langkah 6 / A1: data wajib & tanggal tutup tidak lebih awal dari tanggal buka. */
    private function validasi(Request $request): array
    {
        return $request->validate([
            'nama_program' => ['required', 'string', 'max:150'],
            'tanggal_buka' => ['required', 'date'],
            'tanggal_tutup' => ['required', 'date', 'after_or_equal:tanggal_buka'],
            'informasi' => ['nullable', 'string'],
            'persyaratan' => ['nullable', 'string'],
            'status_publikasi' => ['required', Rule::in(ProgramMagang::STATUS)],
        ], [
            'required' => self::PESAN_TIDAK_VALID,
            'date' => self::PESAN_TIDAK_VALID,
            'in' => self::PESAN_TIDAK_VALID,
            'max' => self::PESAN_TIDAK_VALID,
            'tanggal_tutup.after_or_equal' => 'Data belum lengkap atau periode tidak valid: tanggal tutup tidak boleh lebih awal dari tanggal buka.',
        ]);
    }

    /** Menambah syarat dokumen baru (satu per baris). Syarat lama tidak dihapus agar dokumen yang merujuknya tetap valid. */
    private function simpanSyarat(ProgramMagang $program, ?string $teks): void
    {
        $urutan = (int) $program->syaratDokumenProgram()->max('urutan');
        foreach (preg_split('/\r\n|\r|\n/', (string) $teks) as $baris) {
            if (trim($baris) === '') {
                continue;
            }
            $program->syaratDokumenProgram()->create(['nama_syarat' => trim($baris), 'wajib' => true, 'urutan' => ++$urutan]);
        }
    }
}
