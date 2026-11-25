<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Http\Requests\Peserta\KirimProgresRequest;
use App\Models\ProgresTugas;
use App\Models\Tugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Tugas Magang (Peserta) (UC-07)
 * Aktor  : Peserta Magang
 * Tabel  : tugas, progres_tugas
 */
class TugasController extends Controller
{
    /**
     * Daftar Tugas — GET /peserta/tugas
     */
    public function index(Request $request): View
    {
        $peserta = $this->pesertaLogin();
        abort_unless($peserta, 403, 'Akses ditolak.');

        $query = Tugas::where('id_peserta_magang', $peserta->id_peserta_magang)
            ->with(['pesertaMagang.mentor', 'progresTugas']);

        $totalTugas = (clone $query)->count();
        $tugasDitugaskan = (clone $query)->where('status', 'Ditugaskan')->count();
        $tugasDiserahkan = (clone $query)->where('status', 'Diserahkan')->count();
        $tugasPerluPerbaikan = (clone $query)->where('status', 'Perlu Perbaikan')->count();
        $tugasSelesai = (clone $query)->where('status', 'Selesai')->count();

        if ($request->filled('status') && in_array($request->status, Tugas::STATUS, true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where('judul', 'like', "%{$q}%");
        }

        $daftarTugas = $query->orderByDesc('id_tugas')->paginate(10)->withQueryString();

        return view('peserta.tugas.index', compact(
            'daftarTugas',
            'totalTugas',
            'tugasDitugaskan',
            'tugasDiserahkan',
            'tugasPerluPerbaikan',
            'tugasSelesai'
        ));
    }

    /**
     * Detail Tugas — GET /peserta/tugas/{tugas}
     */
    public function show(Request $request, Tugas $tugas): View
    {
        $this->pastikanMilikSendiri($tugas->pesertaMagang);

        $tugas->load(['pesertaMagang.mentor', 'progresTugas']);
        $bisaKirim = $tugas->apakahBolehKirimProgres() && ($tugas->pesertaMagang?->status_magang === 'Aktif');
        $urutanBerikutnya = $tugas->urutanProgresBerikutnya();

        return view('peserta.tugas.show', compact('tugas', 'bisaKirim', 'urutanBerikutnya'));
    }

    /**
     * Kirim Progres — POST /peserta/tugas/{tugas}/progres
     */
    public function kirimProgres(KirimProgresRequest $request, Tugas $tugas): RedirectResponse
    {
        $this->pastikanMilikSendiri($tugas->pesertaMagang);

        if (! $tugas->apakahBolehKirimProgres() || $tugas->pesertaMagang?->status_magang !== 'Aktif') {
            return back()->with('gagal', 'Tugas saat ini tidak menerima pengiriman progres baru.');
        }

        $file = $request->file('berkas');
        $ekstensi = $file->getClientOriginalExtension();
        $namaDasar = $request->filled('nama_kustom_berkas')
            ? $request->nama_kustom_berkas
            : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $namaSanitasi = Str::slug($namaDasar);
        if (empty($namaSanitasi)) {
            $namaSanitasi = 'berkas_progres';
        }
        $namaSimpan = time() . '_' . $namaSanitasi . '.' . $ekstensi;
        $pathBerkas = $file->storeAs("tugas-berkas/{$tugas->id_tugas}", $namaSimpan, 'local');

        DB::transaction(function () use ($request, $tugas, $pathBerkas) {
            $urutan = ($tugas->progresTugas()->lockForUpdate()->max('urutan') ?? 0) + 1;

            ProgresTugas::create([
                'id_tugas'        => $tugas->id_tugas,
                'urutan'          => $urutan,
                'catatan_progres' => $request->catatan_progres,
                'tautan_hasil'    => $request->tautan_hasil,
                'path_berkas'     => $pathBerkas,
                'waktu_kirim'     => now(),
            ]);

            $tugas->status = 'Diserahkan';
            $tugas->save();
        });

        // TODO: notifikasi progres dikirim ke mentor ($tugas->pesertaMagang->id_mentor)

        return redirect()->route('peserta.tugas.show', $tugas)->with('sukses', 'Progres berhasil dikirim.');
    }
}
