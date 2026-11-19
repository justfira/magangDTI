<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\EvaluasiPeserta;
use App\Models\PesertaMagang;
use App\Support\Notifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Evaluasi Peserta (UC-13)
 * Aktor  : Mentor DTI
 * Tabel  : evaluasi_peserta
 *
 * Status : IMPLEMENTASI — satu evaluasi per peserta (UNIQUE id_peserta_magang),
 *          dibedakan per jalur pengajuan (DTI vs INSTITUSI). Hanya peserta
 *          berstatus Aktif yang dapat dicatat/diubah evaluasinya oleh mentor pembimbing.
 */
class EvaluasiController extends Controller
{
    /**
     * Evaluasi Peserta — GET /mentor/evaluasi
     * Daftar peserta bimbingan & status evaluasinya.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $pegawai = $this->pegawaiLogin();
        if (! $pegawai) {
            return redirect()->route('dashboard')->with('info', 'Halaman ini memerlukan akun mentor dengan data pegawai.');
        }

        $pesertaBimbingan = PesertaMagang::where('id_mentor', $pegawai->id_pegawai)
            ->with(['pengguna', 'keputusan.calon.pengajuan', 'evaluasiPeserta'])
            ->withCount([
                'tugas',
                'tugas as tugas_selesai_count' => fn ($q) => $q->where('status', 'Selesai'),
            ])
            ->orderByRaw("CASE WHEN status_magang = 'Aktif' THEN 0 ELSE 1 END")
            ->orderByDesc('id_peserta_magang')
            ->get();

        return view('mentor.evaluasi.index', compact('pesertaBimbingan'));
    }

    /**
     * Catat Evaluasi — GET /mentor/evaluasi/{peserta}/edit
     * Jalur DTI: Memenuhi / Belum Memenuhi + catatan.
     * Jalur INSTITUSI: Belum Dinilai / Sudah Dinilai + unggah dokumen evaluasi.
     */
    public function edit(Request $request, PesertaMagang $peserta): View|RedirectResponse
    {
        $this->pastikanBimbingan($peserta);

        if ($peserta->status_magang !== 'Aktif') {
            return redirect()->route('mentor.evaluasi.index')
                ->with('info', 'Evaluasi hanya dapat dicatat atau diperbarui untuk peserta berstatus Aktif.');
        }

        $peserta->load(['pengguna', 'keputusan.calon.pengajuan', 'evaluasiPeserta', 'tugas']);
        $jalur = $peserta->keputusan?->calon?->pengajuan?->jalur;
        abort_unless(in_array($jalur, ['DTI', 'INSTITUSI'], true), 404, 'Jalur pengajuan peserta tidak ditemukan.');

        return view('mentor.evaluasi.edit', compact('peserta', 'jalur'));
    }

    /**
     * Simpan Evaluasi — PUT /mentor/evaluasi/{peserta}
     * updateOrCreate evaluasi_peserta (satu per peserta) & kirim notifikasi ke peserta.
     */
    public function update(Request $request, PesertaMagang $peserta): RedirectResponse
    {
        $this->pastikanBimbingan($peserta);

        if ($peserta->status_magang !== 'Aktif') {
            return redirect()->route('mentor.evaluasi.index')
                ->with('info', 'Evaluasi hanya dapat dicatat atau diperbarui untuk peserta berstatus Aktif.');
        }

        $jalur = $peserta->keputusan?->calon?->pengajuan?->jalur;
        abort_unless(in_array($jalur, ['DTI', 'INSTITUSI'], true), 404, 'Jalur pengajuan peserta tidak ditemukan.');

        $evaluasiLama = $peserta->evaluasiPeserta;

        if ($jalur === 'DTI') {
            $data = $request->validate([
                'status_evaluasi' => ['required', Rule::in(EvaluasiPeserta::STATUS_DTI)],
                'catatan' => ['required_if:status_evaluasi,Belum Memenuhi', 'nullable', 'string', 'max:2000'],
            ], [
                'status_evaluasi.required' => 'Status evaluasi wajib dipilih.',
                'status_evaluasi.in' => 'Status evaluasi tidak sesuai dengan jalur Rekrutmen DTI.',
                'catatan.required_if' => 'Catatan pemenuhan tugas wajib diisi apabila status Belum Memenuhi.',
                'catatan.max' => 'Catatan maksimal 2000 karakter.',
            ]);
        } else {
            $wajibDokumen = $request->input('status_evaluasi') === 'Sudah Dinilai'
                && empty($evaluasiLama?->path_dokumen_evaluasi);

            $data = $request->validate([
                'status_evaluasi' => ['required', Rule::in(EvaluasiPeserta::STATUS_INSTITUSI)],
                'dokumen_evaluasi' => [
                    $wajibDokumen ? 'required' : 'nullable',
                    'file',
                    'mimes:pdf,doc,docx,jpg,jpeg,png',
                    'max:5120',
                ],
                'catatan' => ['nullable', 'string', 'max:2000'],
            ], [
                'status_evaluasi.required' => 'Status penilaian wajib dipilih.',
                'status_evaluasi.in' => 'Status penilaian tidak sesuai dengan jalur Institusi.',
                'dokumen_evaluasi.required' => 'Dokumen hasil evaluasi wajib diunggah untuk status Sudah Dinilai.',
                'dokumen_evaluasi.file' => 'Berkas dokumen evaluasi tidak valid.',
                'dokumen_evaluasi.mimes' => 'Dokumen evaluasi harus berformat PDF, DOC, DOCX, JPG, JPEG, atau PNG.',
                'dokumen_evaluasi.max' => 'Ukuran dokumen evaluasi maksimal 5 MB.',
                'catatan.max' => 'Catatan maksimal 2000 karakter.',
            ]);
        }

        $pathDokumen = $jalur === 'INSTITUSI' ? $evaluasiLama?->path_dokumen_evaluasi : null;
        if ($jalur === 'INSTITUSI' && $request->hasFile('dokumen_evaluasi')) {
            $pathBaru = $request->file('dokumen_evaluasi')->store('evaluasi-peserta', 'public');
            if ($evaluasiLama?->path_dokumen_evaluasi && $evaluasiLama->path_dokumen_evaluasi !== $pathBaru) {
                Storage::disk('public')->delete($evaluasiLama->path_dokumen_evaluasi);
            }
            $pathDokumen = $pathBaru;
        }

        $evaluasi = EvaluasiPeserta::updateOrCreate(
            ['id_peserta_magang' => $peserta->id_peserta_magang],
            [
                'status_evaluasi' => $data['status_evaluasi'],
                'catatan' => $data['catatan'] ?? null,
                'path_dokumen_evaluasi' => $pathDokumen,
                'waktu_evaluasi' => now(),
            ],
        );

        Notifikasi::kirim(
            $peserta->id_pengguna,
            'EVALUASI_PESERTA',
            'Hasil evaluasi magang Anda telah dicatat oleh mentor dengan status '.$evaluasi->status_evaluasi.'.',
            'evaluasi_peserta',
            $evaluasi->id_evaluasi,
        );

        return redirect()->route('mentor.evaluasi.index')->with('sukses', 'Evaluasi peserta berhasil disimpan.');
    }
}
