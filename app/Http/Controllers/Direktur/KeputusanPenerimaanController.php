<?php

namespace App\Http\Controllers\Direktur;

use App\Http\Controllers\Controller;
use App\Models\KeputusanPenerimaan;
use App\Services\AlurPenerimaanService as Alur;
use App\Support\Notifikasi;
use App\Support\Peran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * UC-05 (FR-08) Persetujuan Penerimaan — Direktur DTI.
 * Tabel : keputusan_penerimaan (+ pengajuan.status_pengajuan -> "Selesai Diputuskan" bila semua calon sudah diputuskan)
 *
 * Main flow 9–12 : daftar rekomendasi -> detail riwayat pemeriksaan & rekomendasi -> Terima/Tolak (catatan opsional)
 *                  -> simpan keputusan + id & waktu Direktur -> hasil terlihat di akun pendaftar / oleh Superadmin.
 * A3             : Direktur menolak -> keputusan "Ditolak", tetap lanjut ke langkah 12.
 * Banner         : alur ini masih menunggu konfirmasi DTI.
 */
class KeputusanPenerimaanController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'riwayat' ? 'riwayat' : 'menunggu';
        $cari = trim((string) $request->query('cari'));

        $ringkasan = [
            'menunggu' => KeputusanPenerimaan::whereNull('keputusan')->count(),
            'riwayat' => KeputusanPenerimaan::whereNotNull('keputusan')->count(),
        ];

        $daftar = KeputusanPenerimaan::with(['calon.pengajuan'])
            ->when($tab === 'menunggu', fn ($q) => $q->whereNull('keputusan'), fn ($q) => $q->whereNotNull('keputusan'))
            ->when($cari !== '', fn ($q) => $q->whereHas('calon', fn ($c) => $c
                ->where('nama_lengkap', 'like', "%{$cari}%")
                ->orWhere('institusi_asal', 'like', "%{$cari}%")))
            ->orderBy($tab === 'menunggu' ? 'waktu_rekomendasi' : 'waktu_keputusan', $tab === 'menunggu' ? 'asc' : 'desc')
            ->paginate(10)->withQueryString();

        $terpilih = null;
        if ($request->filled('keputusan')) {
            $terpilih = KeputusanPenerimaan::with([
                'calon.pengajuan.program.syaratDokumenProgram', 'calon.pengajuan.dokumenPengajuan', 'calon.pengajuan.pemeriksa.pengguna',
                'kepalaSeksi.pengguna', 'direktur.pengguna',
            ])->find($request->integer('keputusan'));
        }

        return view('direktur.penerimaan.index', [
            'daftar' => $daftar,
            'tab' => $tab,
            'cari' => $cari,
            'ringkasan' => $ringkasan,
            'terpilih' => $terpilih,
            'checklist' => $terpilih ? Alur::checklistDokumen($terpilih->calon->pengajuan) : collect(),
        ]);
    }

    public function update(Request $request, KeputusanPenerimaan $keputusan): RedirectResponse
    {
        $kembali = redirect()->route('direktur.penerimaan.index', ['keputusan' => $keputusan->id_keputusan]);

        if ($keputusan->keputusan !== null) {
            return $kembali->with('gagal', "Rekomendasi ini sudah diputuskan ({$keputusan->keputusan}).");
        }

        $data = $request->validate([
            'keputusan' => ['required', 'in:Diterima,Ditolak'],
            'catatan_keputusan' => ['nullable', 'string', 'max:1000'],
        ], [
            'keputusan.required' => 'Pilih keputusan Terima atau Tolak.',
            'keputusan.in' => 'Keputusan harus Diterima atau Ditolak.',
            'catatan_keputusan.max' => 'Catatan maksimal 1000 karakter.',
        ]);

        $pegawai = $this->pegawaiLogin();
        if (! $pegawai) {
            return $kembali->with('gagal', 'Akun Anda belum terdaftar sebagai pegawai.');
        }

        try {
            DB::transaction(function () use ($keputusan, $data, $pegawai) {
                $terkunci = KeputusanPenerimaan::whereKey($keputusan->id_keputusan)->lockForUpdate()->first();
                if ($terkunci->keputusan !== null) {
                    throw new \RuntimeException('Sudah diputuskan.');
                }

                $terkunci->update([
                    'keputusan' => $data['keputusan'],
                    'catatan_keputusan' => filled($data['catatan_keputusan'] ?? null) ? trim($data['catatan_keputusan']) : null,
                    'id_direktur' => $pegawai->id_pegawai,
                    'waktu_keputusan' => now(),
                ]);

                $calon = $terkunci->calon()->with('pengajuan')->first();
                Alur::tutupJikaSemuaDiputuskan($calon->pengajuan);
                $this->kirimHasil($calon, $data['keputusan']);
            });
        } catch (Throwable $e) {
            report($e);

            return $kembali->withInput()->with('gagal', Alur::PESAN_GAGAL_SIMPAN);
        }

        Log::channel('aktivitas')->info('keputusan_penerimaan', [
            'keputusan' => $keputusan->id_keputusan, 'hasil' => $data['keputusan'], 'oleh' => auth()->id(),
        ]);

        return redirect()->route('direktur.penerimaan.index')
            ->with('sukses', "Keputusan tersimpan: {$keputusan->calon->nama_lengkap} {$data['keputusan']}.");
    }

    /** Langkah 12: hasil ke akun pendaftar (jalur DTI) atau ke Superadmin (jalur institusi). */
    private function kirimHasil($calon, string $hasil): void
    {
        $p = $calon->pengajuan;
        $teks = $hasil === 'Diterima' ? 'DITERIMA' : 'tidak diterima';

        if ($p->jalur === 'DTI' && $p->diajukan_oleh) {
            Notifikasi::kirim($p->diajukan_oleh, 'HASIL_PENERIMAAN',
                $hasil === 'Diterima'
                    ? "Selamat! Pengajuan magang {$p->kode_pengajuan} Anda DITERIMA. Informasi penempatan akan disampaikan selanjutnya."
                    : "Mohon maaf, pengajuan magang {$p->kode_pengajuan} Anda tidak diterima.",
                'pengajuan', $p->id_pengajuan);
        }

        // Superadmin selalu diberi tahu untuk jalur institusi, dan untuk calon Diterima (perlu aktivasi akun peserta, UC-17).
        if ($p->jalur === 'INSTITUSI' || $hasil === 'Diterima') {
            Alur::beritahuPeran(Peran::SUPERADMIN, 'HASIL_PENERIMAAN',
                "{$calon->nama_lengkap} ({$p->kode_pengajuan}".($p->jalur === 'INSTITUSI' ? ", {$p->nama_institusi}" : '').") {$teks} oleh Direktur.",
                'pengajuan', $p->id_pengajuan);
        }
    }
}
