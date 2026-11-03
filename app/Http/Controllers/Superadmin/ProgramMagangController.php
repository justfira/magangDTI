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

/**
 * UC-02 Mengelola Program Magang (Superadmin) — SUDAH DIIMPLEMENTASIKAN.
 * Dipakai juga sebagai CONTOH POLA untuk modul lain: index -> form -> validasi -> simpan (transaksi) -> notifikasi sukses.
 */
class ProgramMagangController extends Controller
{
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

        DB::transaction(function () use ($data, $request) {
            $program = ProgramMagang::create($data + ['dibuat_oleh' => $this->pegawaiLogin()->id_pegawai]);
            $this->simpanSyarat($program, $request->input('syarat_baru'));
        });
        Log::channel('aktivitas')->info('program_dibuat', ['nama' => $data['nama_program'], 'oleh' => auth()->id()]);

        return redirect()->route('superadmin.program-magang.index')->with('sukses', 'Program berhasil disimpan.');
    }

    public function edit(ProgramMagang $programMagang): View
    {
        return view('superadmin.program.form', ['program' => $programMagang->load('syaratDokumenProgram')]);
    }

    public function update(Request $request, ProgramMagang $programMagang): RedirectResponse
    {
        $data = $this->validasi($request);

        DB::transaction(function () use ($data, $request, $programMagang) {
            $programMagang->update($data);
            $this->simpanSyarat($programMagang, $request->input('syarat_baru'));
        });
        Log::channel('aktivitas')->info('program_diubah', ['id_program' => $programMagang->id_program, 'oleh' => auth()->id()]);

        return redirect()->route('superadmin.program-magang.index')->with('sukses', 'Program berhasil disimpan.');
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
            'tanggal_tutup.after_or_equal' => 'Data belum lengkap atau periode tidak valid: tanggal tutup tidak boleh lebih awal dari tanggal buka.',
            'required' => 'Data belum lengkap atau periode tidak valid.',
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
