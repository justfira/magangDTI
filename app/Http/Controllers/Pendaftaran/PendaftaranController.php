<?php

namespace App\Http\Controllers\Pendaftaran;

use App\Http\Controllers\Controller;
use App\Http\Requests\PendaftaranRequest;
use App\Models\CalonPeserta;
use App\Models\DokumenPengajuan;
use App\Models\Pengajuan;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\ProgramMagang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * Mendaftar Rekrutmen DTI (UC-03 / FR-04)
 * Aktor  : Calon Peserta
 * Tabel  : pengguna, pengajuan, calon_peserta, dokumen_pengajuan
 */
class PendaftaranController extends Controller
{
    /**
     * Form Pendaftaran — GET /daftar/{program}
     */
    public function create(Request $request, ProgramMagang $program): View|RedirectResponse
    {
        if (! $program->isBuka()) {
            return redirect()->route('informasi-program')->with('gagal', 'Pendaftaran ditutup.');
        }

        $program->load(['syaratDokumenProgram' => fn ($q) => $q->orderBy('urutan')]);

        return view('pendaftaran.create', compact('program'));
    }

    /**
     * Kirim Pendaftaran — POST /daftar/{program}
     */
    public function store(PendaftaranRequest $request, ProgramMagang $program): RedirectResponse
    {
        if (! $program->isBuka()) {
            return redirect()->route('informasi-program')->with('gagal', 'Pendaftaran ditutup.');
        }

        // Cek duplikasi email
        if (Pengguna::where('email', $request->email)->exists()) {
            return back()->withInput()
                ->withErrors(['email' => 'Email sudah terdaftar atau sudah mendaftar pada program ini.'])
                ->with('gagal', 'Email sudah terdaftar atau sudah mendaftar pada program ini.');
        }

        try {
            $pengguna = DB::transaction(function () use ($request, $program) {
                // 1. Akun Pengguna (peran PESERTA)
                $idPeranPeserta = Peran::where('kode_peran', \App\Support\Peran::PESERTA)->value('id_peran');
                $pengguna = Pengguna::create([
                    'id_peran' => $idPeranPeserta,
                    'nama_lengkap' => $request->nama_lengkap,
                    'email' => $request->email,
                    'password_hash' => Hash::make($request->password),
                    'status_akun' => 'Aktif',
                ]);

                // 2. Buat kode pengajuan DTI-{TAHUN}-{URUT 4 DIGIT} dengan penguncian
                $tahun = now()->format('Y');
                $prefix = "DTI-{$tahun}-";
                $terakhir = Pengajuan::where('kode_pengajuan', 'like', "{$prefix}%")
                    ->lockForUpdate()
                    ->orderByDesc('kode_pengajuan')
                    ->value('kode_pengajuan');

                $nomorUrut = 1;
                if ($terakhir && preg_match('/-(\d+)$/', $terakhir, $m)) {
                    $nomorUrut = ((int) $m[1]) + 1;
                }
                $kodePengajuan = sprintf('%s%04d', $prefix, $nomorUrut);
                while (Pengajuan::where('kode_pengajuan', $kodePengajuan)->exists()) {
                    $nomorUrut++;
                    $kodePengajuan = sprintf('%s%04d', $prefix, $nomorUrut);
                }

                // 3. Simpan Pengajuan (jalur DTI, status Diajukan)
                $pengajuan = Pengajuan::create([
                    'id_program' => $program->id_program,
                    'diajukan_oleh' => $pengguna->id_pengguna,
                    'dicatat_oleh' => null,
                    'id_pemeriksa' => null,
                    'kode_pengajuan' => $kodePengajuan,
                    'jalur' => 'DTI',
                    'status_pengajuan' => 'Diajukan',
                    'catatan_perbaikan' => null,
                    'waktu_pemeriksaan' => null,
                    'tanggal_pengajuan' => now(),
                ]);

                // 4. Simpan Calon Peserta
                CalonPeserta::create([
                    'id_pengajuan' => $pengajuan->id_pengajuan,
                    'nama_lengkap' => $request->nama_lengkap,
                    'email' => $request->email,
                    'no_hp' => $request->no_hp,
                    'nomor_induk_pendidikan' => $request->nomor_induk_pendidikan,
                    'institusi_asal' => $request->institusi_asal,
                    'jurusan_prodi' => $request->jurusan_prodi,
                ]);

                // 5. Simpan Dokumen Persyaratan ke disk privat (local)
                $dokumenInput = $request->file('dokumen') ?? [];
                foreach ($program->syaratDokumenProgram as $syarat) {
                    if (isset($dokumenInput[$syarat->id_syarat])) {
                        $file = $dokumenInput[$syarat->id_syarat];
                        $ekstensi = $file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'bin');
                        $namaUnik = Str::random(40) . '.' . $ekstensi;
                        $path = $file->storeAs("dokumen-pengajuan/{$pengajuan->id_pengajuan}", $namaUnik, 'local');

                        if (! $path) {
                            throw new \RuntimeException('Gagal mengunggah berkas.');
                        }

                        DokumenPengajuan::create([
                            'id_pengajuan' => $pengajuan->id_pengajuan,
                            'id_syarat' => $syarat->id_syarat,
                            'jenis_dokumen' => $syarat->nama_syarat,
                            'nama_berkas' => $file->getClientOriginalName(),
                            'path_berkas' => $path,
                            'ukuran_bytes' => $file->getSize(),
                            'uploaded_at' => now(),
                            'hasil_pemeriksaan' => null,
                            'catatan_pemeriksaan' => null,
                        ]);
                    }
                }

                // TODO: notifikasi ke Superadmin (ada pengajuan baru)

                return $pengguna;
            });

            Auth::login($pengguna);

            return redirect()->route('peserta.pendaftaran.index')
                ->with('sukses', 'Pendaftaran berhasil dikirim.');
        } catch (\Illuminate\Http\Exceptions\PostTooLargeException $e) {
            return back()->withInput()->with('gagal', 'Ukuran berkas melebihi 2 MB.');
        } catch (Throwable $e) {
            report($e);
            return back()->withInput()->with('gagal', 'Gagal menyimpan pendaftaran, silakan coba lagi.');
        }
    }
}
