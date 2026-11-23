<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Http\Requests\PerbaikanPendaftaranRequest;
use App\Models\DokumenPengajuan;
use App\Models\Pengajuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * Status & Perbaikan Pendaftaran (UC-03 / UC-05)
 * Aktor  : Calon Peserta
 * Tabel  : pengajuan, dokumen_pengajuan, keputusan_penerimaan
 */
class StatusPendaftaranController extends Controller
{
    /**
     * Status Pendaftaran — GET /peserta/pendaftaran
     */
    public function index(Request $request): View
    {
        $daftarPengajuan = $request->user()
            ->pengajuanSaya()
            ->with([
                'program.syaratDokumenProgram' => fn ($q) => $q->orderBy('urutan'),
                'dokumenPengajuan.syarat',
                'calonPeserta.keputusanPenerimaan',
            ])
            ->orderByDesc('tanggal_pengajuan')
            ->get();

        return view('peserta.pendaftaran.index', compact('daftarPengajuan'));
    }

    /**
     * Form Perbaikan Pendaftaran — GET /peserta/pendaftaran/{pengajuan}/perbaikan
     */
    public function edit(Request $request, Pengajuan $pengajuan): View
    {
        abort_unless($pengajuan->diajukan_oleh === $request->user()->id_pengguna, 403, 'Anda tidak memiliki akses ke pengajuan ini.');
        abort_unless($pengajuan->status_pengajuan === 'Perlu Perbaikan', 403, 'Pengajuan ini tidak sedang dalam status Perlu Perbaikan.');

        $pengajuan->load([
            'program.syaratDokumenProgram' => fn ($q) => $q->orderBy('urutan'),
            'dokumenPengajuan',
            'calonPeserta',
        ]);

        return view('peserta.pendaftaran.edit', compact('pengajuan'));
    }

    /**
     * Kirim Perbaikan — PUT /peserta/pendaftaran/{pengajuan}/perbaikan
     */
    public function update(PerbaikanPendaftaranRequest $request, Pengajuan $pengajuan): RedirectResponse
    {
        abort_unless($pengajuan->diajukan_oleh === $request->user()->id_pengguna, 403, 'Anda tidak memiliki akses ke pengajuan ini.');
        abort_unless($pengajuan->status_pengajuan === 'Perlu Perbaikan', 403, 'Pengajuan ini tidak sedang dalam status Perlu Perbaikan.');

        $berkasLamaAkanDihapus = [];

        try {
            DB::transaction(function () use ($request, $pengajuan, &$berkasLamaAkanDihapus) {
                // 1. Perbarui calon_peserta
                $calon = $pengajuan->calonPeserta()->first();
                if ($calon) {
                    $calon->update([
                        'nama_lengkap' => $request->nama_lengkap,
                        'no_hp' => $request->no_hp,
                        'nomor_induk_pendidikan' => $request->nomor_induk_pendidikan,
                        'institusi_asal' => $request->institusi_asal,
                        'jurusan_prodi' => $request->jurusan_prodi,
                    ]);
                }

                // Sinkronkan nama lengkap pengguna akun jika berubah
                $pengguna = $pengajuan->diajukanOleh;
                if ($pengguna && $pengguna->nama_lengkap !== $request->nama_lengkap) {
                    $pengguna->update(['nama_lengkap' => $request->nama_lengkap]);
                }

                // 2. Unggah ulang berkas yang diganti (MENIMPA baris dokumen_pengajuan untuk syarat yang sama)
                $dokumenInput = $request->file('dokumen') ?? [];
                $syaratList = $pengajuan->program?->syaratDokumenProgram ?? collect();

                foreach ($syaratList as $syarat) {
                    if (isset($dokumenInput[$syarat->id_syarat])) {
                        $file = $dokumenInput[$syarat->id_syarat];
                        $dokumenLama = $pengajuan->dokumenPengajuan()
                            ->where('id_syarat', $syarat->id_syarat)
                            ->first();

                        $ekstensi = $file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'bin');
                        $namaUnik = Str::random(40) . '.' . $ekstensi;
                        $pathBaru = $file->storeAs("dokumen-pengajuan/{$pengajuan->id_pengajuan}", $namaUnik, 'local');

                        if (! $pathBaru) {
                            throw new \RuntimeException('Gagal mengunggah berkas.');
                        }

                        if ($dokumenLama) {
                            $berkasLamaAkanDihapus[] = $dokumenLama->path_berkas;

                            $dokumenLama->update([
                                'nama_berkas' => $file->getClientOriginalName(),
                                'path_berkas' => $pathBaru,
                                'ukuran_bytes' => $file->getSize(),
                                'uploaded_at' => now(),
                                'hasil_pemeriksaan' => null,
                                'catatan_pemeriksaan' => null,
                            ]);
                        } else {
                            $pengajuan->dokumenPengajuan()->create([
                                'id_syarat' => $syarat->id_syarat,
                                'jenis_dokumen' => $syarat->nama_syarat,
                                'nama_berkas' => $file->getClientOriginalName(),
                                'path_berkas' => $pathBaru,
                                'ukuran_bytes' => $file->getSize(),
                                'uploaded_at' => now(),
                                'hasil_pemeriksaan' => null,
                                'catatan_pemeriksaan' => null,
                            ]);
                        }
                    }
                }

                // 3. Kembalikan status pengajuan menjadi Diajukan
                $pengajuan->update([
                    'status_pengajuan' => 'Diajukan',
                ]);

                // TODO: notifikasi ke Superadmin (perbaikan pendaftaran dikirim)
            });

            // Hapus berkas fisik lama setelah transaksi sukses
            foreach ($berkasLamaAkanDihapus as $pathLama) {
                if ($pathLama && Storage::disk('local')->exists($pathLama)) {
                    Storage::disk('local')->delete($pathLama);
                }
            }

            return redirect()->route('peserta.pendaftaran.index')
                ->with('sukses', 'Perbaikan berhasil dikirim.');
        } catch (Throwable $e) {
            report($e);
            return back()->withInput()->with('gagal', 'Gagal menyimpan pendaftaran, silakan coba lagi.');
        }
    }
}
