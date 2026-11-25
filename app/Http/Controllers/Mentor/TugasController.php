<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mentor\ReviewProgresRequest;
use App\Http\Requests\Mentor\SimpanTugasRequest;
use App\Http\Requests\Mentor\UbahTugasRequest;
use App\Models\PesertaMagang;
use App\Models\ProgresTugas;
use App\Models\Tugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Tugas Magang (Mentor) (UC-07)
 * Aktor  : Mentor DTI
 * Tabel  : tugas, progres_tugas
 */
class TugasController extends Controller
{
    /**
     * Tugas Peserta Bimbingan — GET /mentor/tugas
     */
    public function index(Request $request): View
    {
        $mentor = $this->pegawaiLogin();
        abort_unless($mentor, 403, 'Akses ditolak.');

        $pesertaBimbinganIds = PesertaMagang::where('id_mentor', $mentor->id_pegawai)
            ->pluck('id_peserta_magang');

        $query = Tugas::whereIn('id_peserta_magang', $pesertaBimbinganIds)
            ->with(['pesertaMagang.keputusan.calon', 'pesertaMagang.pengguna', 'progresTugas']);

        $totalTugas = (clone $query)->count();
        $tugasDiserahkan = (clone $query)->where('status', 'Diserahkan')->count();
        $tugasPerluPerbaikan = (clone $query)->where('status', 'Perlu Perbaikan')->count();
        $tugasSelesai = (clone $query)->where('status', 'Selesai')->count();

        if ($request->filled('status') && in_array($request->status, Tugas::STATUS, true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('judul', 'like', "%{$q}%")
                    ->orWhereHas('pesertaMagang.keputusan.calon', function ($cp) use ($q) {
                        $cp->where('nama_lengkap', 'like', "%{$q}%");
                    })
                    ->orWhereHas('pesertaMagang.pengguna', function ($u) use ($q) {
                        $u->where('nama_lengkap', 'like', "%{$q}%");
                    });
            });
        }

        $daftarTugas = $query->orderByDesc('id_tugas')->paginate(10)->withQueryString();

        return view('mentor.tugas.index', compact(
            'daftarTugas',
            'totalTugas',
            'tugasDiserahkan',
            'tugasPerluPerbaikan',
            'tugasSelesai'
        ));
    }

    /**
     * Buat Tugas — GET /mentor/tugas/create
     */
    public function create(Request $request): View
    {
        $mentor = $this->pegawaiLogin();
        abort_unless($mentor, 403, 'Akses ditolak.');

        $pesertaBimbingan = PesertaMagang::where('id_mentor', $mentor->id_pegawai)
            ->where('status_magang', 'Aktif')
            ->with(['keputusan.calon', 'pengguna'])
            ->get();

        return view('mentor.tugas.create', compact('pesertaBimbingan'));
    }

    /**
     * Simpan Tugas — POST /mentor/tugas
     */
    public function store(SimpanTugasRequest $request): RedirectResponse
    {
        $mentor = $this->pegawaiLogin();
        abort_unless($mentor, 403, 'Akses ditolak.');

        $peserta = PesertaMagang::findOrFail($request->id_peserta_magang);
        $this->pastikanBimbingan($peserta);
        abort_unless($peserta->status_magang === 'Aktif', 422, 'Peserta bimbingan tidak berstatus Aktif.');

        $tugas = DB::transaction(function () use ($request, $peserta) {
            return Tugas::create([
                'id_peserta_magang' => $peserta->id_peserta_magang,
                'judul'             => $request->judul,
                'deskripsi'         => $request->deskripsi,
                'tenggat'           => $request->tenggat,
                'jumlah_progres'    => count($request->judul_progres),
                'judul_progres'     => $request->judul_progres,
                'status'            => 'Ditugaskan',
            ]);
        });

        // TODO: notifikasi tugas baru ke peserta ($peserta->id_pengguna)

        return redirect()->route('mentor.tugas.show', $tugas)->with('sukses', 'Tugas berhasil dibuat.');
    }

    /**
     * Detail Tugas & Progres — GET /mentor/tugas/{tugas}
     */
    public function show(Request $request, Tugas $tugas): View
    {
        $this->pastikanBimbingan($tugas->pesertaMagang);

        $tugas->load(['pesertaMagang.keputusan.calon', 'pesertaMagang.pengguna', 'progresTugas']);
        $progresTerakhir = $tugas->progresTerakhir();
        $bisaReview = ($tugas->status === 'Diserahkan' && $progresTerakhir && $progresTerakhir->hasil_review === null);

        return view('mentor.tugas.show', compact('tugas', 'progresTerakhir', 'bisaReview'));
    }

    /**
     * Form Ubah Tugas — GET /mentor/tugas/{tugas}/edit
     */
    public function edit(Request $request, Tugas $tugas): View
    {
        $this->pastikanBimbingan($tugas->pesertaMagang);

        $tugas->load(['pesertaMagang.keputusan.calon', 'pesertaMagang.pengguna']);

        return view('mentor.tugas.edit', compact('tugas'));
    }

    /**
     * Perbarui Tugas — PUT /mentor/tugas/{tugas}
     */
    public function update(UbahTugasRequest $request, Tugas $tugas): RedirectResponse
    {
        $this->pastikanBimbingan($tugas->pesertaMagang);

        $tugas->update([
            'judul'     => $request->judul,
            'deskripsi' => $request->deskripsi,
            'tenggat'   => $request->tenggat,
        ]);

        return redirect()->route('mentor.tugas.show', $tugas)->with('sukses', 'Data tugas dan tenggat waktu berhasil diperbarui.');
    }

    /**
     * Simpan Review Progres — POST /mentor/progres/{progres}/review
     */
    public function review(ReviewProgresRequest $request, ProgresTugas $progres): RedirectResponse
    {
        $tugas = $progres->tugas;
        abort_unless($tugas, 404, 'Tugas tidak ditemukan.');

        $this->pastikanBimbingan($tugas->pesertaMagang);

        if ($progres->hasil_review !== null) {
            return back()->with('gagal', 'Progres ini sudah diperiksa sebelumnya.');
        }

        if ($tugas->status !== 'Diserahkan') {
            return back()->with('gagal', 'Tugas sedang tidak dalam status menunggu pemeriksaan.');
        }

        DB::transaction(function () use ($request, $progres, $tugas) {
            $progres->update([
                'hasil_review'   => $request->hasil_review,
                'catatan_review' => $request->catatan_review,
                'waktu_review'   => now(),
            ]);

            if ($request->hasil_review === 'Diterima') {
                if ($progres->urutan < $tugas->jumlah_progres) {
                    $tugas->status = 'Ditugaskan';
                } else {
                    $tugas->status = 'Selesai';
                }
            } elseif ($request->hasil_review === 'Perlu Perbaikan') {
                $tugas->status = 'Perlu Perbaikan';
                // Jika progres terakhir mendapat 'Perlu Perbaikan', jumlah_progres otomatis bertambah 1
                if ($progres->urutan >= $tugas->jumlah_progres) {
                    $tugas->jumlah_progres += 1;
                }
            }

            $tugas->save();
        });

        // TODO: notifikasi hasil review ke peserta ($tugas->pesertaMagang->id_pengguna)

        return redirect()->route('mentor.tugas.show', $tugas)->with('sukses', 'Hasil pemeriksaan tersimpan.');
    }
}
