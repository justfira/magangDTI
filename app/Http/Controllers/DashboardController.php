<?php

namespace App\Http\Controllers;

use App\Models\CalonPeserta;
use App\Models\KeputusanPenerimaan;
use App\Models\Logbook;
use App\Models\Pengajuan;
use App\Models\PengajuanKehadiran;
use App\Models\PenyelesaianMagang;
use App\Models\PesertaMagang;
use App\Models\ProgramMagang;
use App\Models\ProgresTugas;
use App\Support\Peran;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Dashboard menyesuaikan peran pengguna (UC-01 langkah 8, UC-16). */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $pengguna = $request->user();
        $kartu = [];
        $judul = 'Dashboard';

        switch ($pengguna->kodePeran()) {
            case Peran::SUPERADMIN:
                $judul = 'Dashboard Superadmin';
                $kartu = [
                    'Pengajuan menunggu pemeriksaan' => Pengajuan::where('status_pengajuan', 'Diajukan')->count(),
                    'Program dipublikasikan' => ProgramMagang::where('status_publikasi', 'Dipublikasikan')->count(),
                    'Peserta aktif' => PesertaMagang::aktif()->count(),
                    'Sertifikat belum diunggah' => PenyelesaianMagang::where('keputusan', 'Disetujui')->whereNull('path_sertifikat')->count(),
                ];
                break;
            case Peran::KEPALA_SEKSI:
                $judul = 'Dashboard Kepala Seksi';
                $kartu = [
                    'Calon siap direkomendasikan' => CalonPeserta::whereHas('pengajuan', fn ($q) => $q->where('status_pengajuan', 'Lengkap'))
                        ->doesntHave('keputusanPenerimaan')->count(),
                    'Peserta belum ditempatkan' => PesertaMagang::whereNull('id_mentor')->whereIn('status_magang', ['Belum Mulai', 'Aktif'])->count(),
                    'Peserta aktif' => PesertaMagang::aktif()->count(),
                ];
                break;
            case Peran::DIREKTUR:
                $judul = 'Dashboard Direktur';
                $kartu = [
                    'Keputusan penerimaan menunggu' => KeputusanPenerimaan::whereNull('keputusan')->count(),
                    'Keputusan penyelesaian menunggu' => PenyelesaianMagang::whereNull('keputusan')->count(),
                    'Peserta aktif' => PesertaMagang::aktif()->count(),
                ];
                break;
            case Peran::MENTOR:
                $judul = 'Dashboard Mentor';
                $idMentor = $pengguna->pegawai?->id_pegawai;
                $bimbingan = fn ($q) => $q->where('id_mentor', $idMentor);
                $kartu = [
                    'Peserta bimbingan' => PesertaMagang::where('id_mentor', $idMentor)->count(),
                    'Progres menunggu review' => ProgresTugas::whereNull('hasil_review')->whereHas('tugas.pesertaMagang', $bimbingan)->count(),
                    'Izin / susulan menunggu' => PengajuanKehadiran::where('status', 'Menunggu Verifikasi')->whereHas('pesertaMagang', $bimbingan)->count(),
                    'Logbook menunggu diperiksa' => Logbook::where('status', 'Dikirim')->whereHas('pesertaMagang', $bimbingan)->count(),
                ];
                break;
            case Peran::PESERTA:
                $peserta = $pengguna->pesertaMagangBerjalan();
                if ($peserta) {
                    $judul = 'Dashboard Peserta Magang';
                    $kartu = [
                        'Status magang' => $peserta->status_magang,
                        'Tugas belum selesai' => $peserta->tugas()->where('status', '!=', 'Selesai')->count(),
                        'Hadir bulan ini' => $peserta->presensi()->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)->count(),
                        'Presensi hari ini' => $peserta->presensi()->whereDate('tanggal', today())->exists() ? 'Sudah' : 'Belum',
                    ];
                } else {
                    $judul = 'Dashboard Calon Peserta';
                    $pengajuan = $pengguna->pengajuanSaya()->with('program')->latest('id_pengajuan')->get();

                    return view('dashboard', compact('judul', 'kartu', 'pengajuan'));
                }
                break;
        }

        return view('dashboard', compact('judul', 'kartu'));
    }
}
