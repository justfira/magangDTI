<?php

use App\Http\Controllers;
use Illuminate\Support\Facades\Route;

/*
 | Route Sistem Pengelolaan Magang DTI.
 | Hak akses per peran dijaga middleware 'peran:...' (App\Http\Middleware\CekPeran).
 | Lihat daftar lengkap: php artisan route:list
 */

// ---------- Publik (UC-02 sisi calon peserta, UC-01 login)
Route::get('/', [Controllers\Publik\InformasiProgramController::class, 'index'])->name('informasi-program');

Route::middleware('guest')->group(function () {
    Route::get('/login', [Controllers\Auth\LoginController::class, 'create'])->name('login');
    Route::post('/login', [Controllers\Auth\LoginController::class, 'store'])->middleware('throttle:5,1');
});

// ---------- UC-03 Pendaftaran (boleh tanpa login: akun dibuat saat mendaftar)
Route::get('/daftar/{program}', [Controllers\Pendaftaran\PendaftaranController::class, 'create'])->name('pendaftaran.create');
Route::post('/daftar/{program}', [Controllers\Pendaftaran\PendaftaranController::class, 'store'])->name('pendaftaran.store');

// ---------- Semua pengguna yang sudah login
Route::middleware(['auth', 'akun.aktif'])->group(function () {
    Route::post('/logout', [Controllers\Auth\LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', Controllers\DashboardController::class)->name('dashboard');
    Route::get('/notifikasi', [Controllers\NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/baca-semua', [Controllers\NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::post('/notifikasi/{notifikasi}/baca', [Controllers\NotifikasiController::class, 'baca'])->name('notifikasi.baca');

    // ===== Superadmin
    Route::middleware('peran:SUPERADMIN')->group(function () {
        Route::resource('superadmin/program-magang', Controllers\Superadmin\ProgramMagangController::class)
            ->only(['index', 'create', 'store', 'edit', 'update'])->names('superadmin.program-magang');   // UC-02 (sudah jadi)
        Route::get('/superadmin/permohonan-institusi', [Controllers\Superadmin\PermohonanInstitusiController::class, 'index'])->name('superadmin.permohonan.index');
        Route::get('/superadmin/permohonan-institusi/create', [Controllers\Superadmin\PermohonanInstitusiController::class, 'create'])->name('superadmin.permohonan.create');
        Route::post('/superadmin/permohonan-institusi', [Controllers\Superadmin\PermohonanInstitusiController::class, 'store'])->name('superadmin.permohonan.store');
        Route::get('/superadmin/verifikasi-pengajuan', [Controllers\Superadmin\VerifikasiPengajuanController::class, 'index'])->name('superadmin.verifikasi.index');
        Route::get('/superadmin/verifikasi-pengajuan/{pengajuan}', [Controllers\Superadmin\VerifikasiPengajuanController::class, 'show'])->name('superadmin.verifikasi.show');
        Route::post('/superadmin/verifikasi-pengajuan/{pengajuan}/periksa', [Controllers\Superadmin\VerifikasiPengajuanController::class, 'periksa'])->name('superadmin.verifikasi.periksa');
        Route::get('/superadmin/akun', [Controllers\Superadmin\AkunController::class, 'index'])->name('superadmin.akun.index');
        Route::get('/superadmin/akun/create', [Controllers\Superadmin\AkunController::class, 'create'])->name('superadmin.akun.create');
        Route::post('/superadmin/akun', [Controllers\Superadmin\AkunController::class, 'store'])->name('superadmin.akun.store');
        Route::get('/superadmin/akun/{pengguna}/edit', [Controllers\Superadmin\AkunController::class, 'edit'])->name('superadmin.akun.edit');
        Route::put('/superadmin/akun/{pengguna}', [Controllers\Superadmin\AkunController::class, 'update'])->name('superadmin.akun.update');
        Route::post('/superadmin/akun/{pengguna}/nonaktifkan', [Controllers\Superadmin\AkunController::class, 'nonaktifkan'])->name('superadmin.akun.nonaktifkan');
        Route::post('/superadmin/akun/{pengguna}/aktifkan', [Controllers\Superadmin\AkunController::class, 'aktifkan'])->name('superadmin.akun.aktifkan');
        Route::get('/superadmin/akun/peserta-diterima', [Controllers\Superadmin\AkunController::class, 'pesertaDiterima'])->name('superadmin.akun.diterima');
        Route::post('/superadmin/akun/peserta-diterima/{keputusan}', [Controllers\Superadmin\AkunController::class, 'aktivasiPeserta'])->name('superadmin.akun.aktivasi');
        Route::get('/superadmin/presensi', [Controllers\Superadmin\KelolaPresensiController::class, 'index'])->name('superadmin.presensi.index');
        Route::get('/superadmin/presensi/rekap', [Controllers\Superadmin\KelolaPresensiController::class, 'rekap'])->name('superadmin.presensi.rekap');
        Route::get('/superadmin/presensi/rekap/csv', [Controllers\Superadmin\KelolaPresensiController::class, 'eksporCsv'])->name('superadmin.presensi.csv');
        Route::get('/superadmin/presensi/{presensi}/koreksi', [Controllers\Superadmin\KelolaPresensiController::class, 'koreksi'])->name('superadmin.presensi.koreksi');
        Route::post('/superadmin/presensi/{presensi}/koreksi', [Controllers\Superadmin\KelolaPresensiController::class, 'simpanKoreksi'])->name('superadmin.presensi.koreksi.simpan');
        Route::get('/superadmin/sertifikat', [Controllers\Superadmin\SertifikatController::class, 'index'])->name('superadmin.sertifikat.index');
        Route::post('/superadmin/sertifikat/{penyelesaian}/unggah', [Controllers\Superadmin\SertifikatController::class, 'unggah'])->name('superadmin.sertifikat.unggah');
    });

    // ===== Kepala Seksi
    Route::middleware('peran:KEPALA_SEKSI')->group(function () {
        Route::get('/kepala-seksi/rekomendasi-penerimaan', [Controllers\KepalaSeksi\RekomendasiPenerimaanController::class, 'index'])->name('kasi.rekomendasi.index');
        Route::post('/kepala-seksi/rekomendasi-penerimaan/{calon}', [Controllers\KepalaSeksi\RekomendasiPenerimaanController::class, 'store'])->name('kasi.rekomendasi.store');
        Route::get('/kepala-seksi/penempatan', [Controllers\KepalaSeksi\PenempatanController::class, 'index'])->name('kasi.penempatan.index');
        Route::get('/kepala-seksi/penempatan/{peserta}/edit', [Controllers\KepalaSeksi\PenempatanController::class, 'edit'])->name('kasi.penempatan.edit');
        Route::put('/kepala-seksi/penempatan/{peserta}', [Controllers\KepalaSeksi\PenempatanController::class, 'update'])->name('kasi.penempatan.update');
        Route::get('/kepala-seksi/penyelesaian', [Controllers\KepalaSeksi\PenyelesaianController::class, 'index'])->name('kasi.penyelesaian.index');
        Route::get('/kepala-seksi/penyelesaian/{peserta}', [Controllers\KepalaSeksi\PenyelesaianController::class, 'show'])->name('kasi.penyelesaian.show');
        Route::post('/kepala-seksi/penyelesaian/{peserta}', [Controllers\KepalaSeksi\PenyelesaianController::class, 'store'])->name('kasi.penyelesaian.store');
    });

    // ===== Direktur
    Route::middleware('peran:DIREKTUR')->group(function () {
        Route::get('/direktur/keputusan-penerimaan', [Controllers\Direktur\KeputusanPenerimaanController::class, 'index'])->name('direktur.penerimaan.index');
        Route::put('/direktur/keputusan-penerimaan/{keputusan}', [Controllers\Direktur\KeputusanPenerimaanController::class, 'update'])->name('direktur.penerimaan.update');
        Route::get('/direktur/keputusan-penyelesaian', [Controllers\Direktur\KeputusanPenyelesaianController::class, 'index'])->name('direktur.penyelesaian.index');
        Route::put('/direktur/keputusan-penyelesaian/{penyelesaian}', [Controllers\Direktur\KeputusanPenyelesaianController::class, 'update'])->name('direktur.penyelesaian.update');
    });

    // ===== Mentor
    Route::middleware('peran:MENTOR')->group(function () {
        Route::get('/mentor/peserta-bimbingan', [Controllers\Mentor\PesertaBimbinganController::class, 'index'])->name('mentor.bimbingan.index');
        Route::get('/mentor/tugas', [Controllers\Mentor\TugasController::class, 'index'])->name('mentor.tugas.index');
        Route::get('/mentor/tugas/create', [Controllers\Mentor\TugasController::class, 'create'])->name('mentor.tugas.create');
        Route::post('/mentor/tugas', [Controllers\Mentor\TugasController::class, 'store'])->name('mentor.tugas.store');
        Route::get('/mentor/tugas/{tugas}', [Controllers\Mentor\TugasController::class, 'show'])->name('mentor.tugas.show');
        Route::post('/mentor/progres/{progres}/review', [Controllers\Mentor\TugasController::class, 'review'])->name('mentor.progres.review');
        Route::get('/mentor/verifikasi-kehadiran', [Controllers\Mentor\VerifikasiKehadiranController::class, 'index'])->name('mentor.kehadiran.index');
        Route::post('/mentor/verifikasi-kehadiran/{pengajuanKehadiran}/setujui', [Controllers\Mentor\VerifikasiKehadiranController::class, 'setujui'])->name('mentor.kehadiran.setujui');
        Route::post('/mentor/verifikasi-kehadiran/{pengajuanKehadiran}/tolak', [Controllers\Mentor\VerifikasiKehadiranController::class, 'tolak'])->name('mentor.kehadiran.tolak');
        Route::get('/mentor/logbook', [Controllers\Mentor\PeriksaLogbookController::class, 'index'])->name('mentor.logbook.index');
        Route::post('/mentor/logbook/{logbook}/periksa', [Controllers\Mentor\PeriksaLogbookController::class, 'periksa'])->name('mentor.logbook.periksa');
        Route::get('/mentor/evaluasi', [Controllers\Mentor\EvaluasiController::class, 'index'])->name('mentor.evaluasi.index');
        Route::get('/mentor/evaluasi/{peserta}/edit', [Controllers\Mentor\EvaluasiController::class, 'edit'])->name('mentor.evaluasi.edit');
        Route::put('/mentor/evaluasi/{peserta}', [Controllers\Mentor\EvaluasiController::class, 'update'])->name('mentor.evaluasi.update');
    });

    // ===== Calon / Peserta Magang
    Route::middleware('peran:PESERTA')->group(function () {
        Route::get('/peserta/pendaftaran', [Controllers\Peserta\StatusPendaftaranController::class, 'index'])->name('peserta.pendaftaran.index');
        Route::get('/peserta/pendaftaran/{pengajuan}/perbaikan', [Controllers\Peserta\StatusPendaftaranController::class, 'edit'])->name('peserta.pendaftaran.edit');
        Route::put('/peserta/pendaftaran/{pengajuan}/perbaikan', [Controllers\Peserta\StatusPendaftaranController::class, 'update'])->name('peserta.pendaftaran.update');
        Route::get('/peserta/penempatan', [Controllers\Peserta\PenempatanController::class, 'show'])->name('peserta.penempatan');
        Route::get('/peserta/tugas', [Controllers\Peserta\TugasController::class, 'index'])->name('peserta.tugas.index');
        Route::get('/peserta/tugas/{tugas}', [Controllers\Peserta\TugasController::class, 'show'])->name('peserta.tugas.show');
        Route::post('/peserta/tugas/{tugas}/progres', [Controllers\Peserta\TugasController::class, 'kirimProgres'])->name('peserta.tugas.progres');
        Route::get('/peserta/presensi', [Controllers\Peserta\PresensiController::class, 'index'])->name('peserta.presensi.index');
        Route::post('/peserta/presensi/check-in', [Controllers\Peserta\PresensiController::class, 'checkIn'])->name('peserta.presensi.checkin');
        Route::post('/peserta/presensi/check-out', [Controllers\Peserta\PresensiController::class, 'checkOut'])->name('peserta.presensi.checkout');
        Route::get('/peserta/pengajuan-kehadiran', [Controllers\Peserta\PengajuanKehadiranController::class, 'index'])->name('peserta.kehadiran.index');
        Route::get('/peserta/pengajuan-kehadiran/create', [Controllers\Peserta\PengajuanKehadiranController::class, 'create'])->name('peserta.kehadiran.create');
        Route::post('/peserta/pengajuan-kehadiran', [Controllers\Peserta\PengajuanKehadiranController::class, 'store'])->name('peserta.kehadiran.store');
        Route::get('/peserta/logbook', [Controllers\Peserta\LogbookController::class, 'index'])->name('peserta.logbook.index');
        Route::get('/peserta/logbook/create', [Controllers\Peserta\LogbookController::class, 'create'])->name('peserta.logbook.create');
        Route::post('/peserta/logbook', [Controllers\Peserta\LogbookController::class, 'store'])->name('peserta.logbook.store');
        Route::get('/peserta/logbook/{logbook}/edit', [Controllers\Peserta\LogbookController::class, 'edit'])->name('peserta.logbook.edit');
        Route::put('/peserta/logbook/{logbook}', [Controllers\Peserta\LogbookController::class, 'update'])->name('peserta.logbook.update');
        Route::get('/peserta/sertifikat', [Controllers\Peserta\SertifikatController::class, 'index'])->name('peserta.sertifikat.index');
        Route::get('/peserta/sertifikat/{penyelesaian}/unduh', [Controllers\Peserta\SertifikatController::class, 'unduh'])->name('peserta.sertifikat.unduh');
    });

    // ===== Monitoring (UC-16)
    Route::middleware('peran:SUPERADMIN,KEPALA_SEKSI,DIREKTUR')->group(function () {
        Route::get('/monitoring', [Controllers\MonitoringController::class, 'index'])->name('monitoring');
    });

});
