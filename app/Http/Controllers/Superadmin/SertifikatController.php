<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\PenyelesaianMagang;
use App\Support\Notifikasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Sertifikat (Superadmin) (UC-15) — SUDAH DIIMPLEMENTASIKAN.
 * Aktor  : Superadmin
 * Tabel  : penyelesaian_magang
 *
 * Aturan:
 *  - Hanya penyelesaian dengan keputusan 'Disetujui' yang boleh diberi sertifikat (A1; dijaga juga CHECK di DB).
 *  - File PDF maks. 5 MB disimpan di disk privat 'local' (storage/app/private/sertifikat).
 *  - Unggah ulang boleh; file lama dihapus setelah file baru tersimpan.
 */
class SertifikatController extends Controller
{
    private const DISK = 'local';
    private const FOLDER = 'sertifikat';
    private const TAB = ['belum' => 'Belum Diunggah', 'sudah' => 'Sudah Diunggah'];

    /**
     * Sertifikat Magang — GET /superadmin/sertifikat
     * Daftar penyelesaian Disetujui, dipisah menurut status unggah sertifikat.
     */
    public function index(Request $request): View
    {
        $tab = array_key_exists($request->query('tab'), self::TAB) ? $request->query('tab') : 'belum';

        $penyelesaian = PenyelesaianMagang::query()
            ->where('keputusan', 'Disetujui')
            ->with(['pesertaMagang.pengguna', 'pesertaMagang.seksi', 'pengunggah.pengguna'])
            ->when($tab === 'belum', fn (Builder $q) => $q->whereNull('path_sertifikat')->orderBy('waktu_keputusan'))
            ->when($tab === 'sudah', fn (Builder $q) => $q->whereNotNull('path_sertifikat')->orderByDesc('waktu_unggah'))
            ->paginate(10)
            ->withQueryString();

        $jumlahBelum = PenyelesaianMagang::where('keputusan', 'Disetujui')->whereNull('path_sertifikat')->count();

        return view('superadmin.sertifikat.index', [
            'penyelesaian' => $penyelesaian,
            'tab' => $tab,
            'daftarTab' => self::TAB,
            'jumlahBelum' => $jumlahBelum,
        ]);
    }

    /**
     * Unggah Sertifikat — POST /superadmin/sertifikat/{penyelesaian}/unggah
     * Validasi PDF; simpan ke storage privat; isi nomor_sertifikat, path_sertifikat, id_pengunggah, waktu_unggah; notifikasi peserta.
     */
    public function unggah(Request $request, PenyelesaianMagang $penyelesaian): RedirectResponse
    {
        // A1: sertifikat hanya untuk penyelesaian yang disetujui Direktur.
        if ($penyelesaian->keputusan !== 'Disetujui') {
            return redirect()->route('superadmin.sertifikat.index')
                ->withErrors(['sertifikat' => 'Sertifikat hanya dapat diunggah untuk penyelesaian yang sudah disetujui Direktur.']);
        }

        $pengunggah = $this->pegawaiLogin();
        abort_if($pengunggah === null, 403, 'Akun Anda tidak terdaftar sebagai pegawai.');

        // Error bag per baris agar pesan & isian lama hanya muncul di baris yang dikirim.
        $data = $request->validateWithBag('sertifikat_'.$penyelesaian->id_penyelesaian, [
            'nomor_sertifikat' => [
                'required', 'string', 'max:100',
                Rule::unique('penyelesaian_magang', 'nomor_sertifikat')->ignore($penyelesaian->id_penyelesaian, 'id_penyelesaian'),
            ],
            'file_sertifikat' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'nomor_sertifikat.required' => 'Nomor sertifikat wajib diisi.',
            'nomor_sertifikat.max' => 'Nomor sertifikat maksimal 100 karakter.',
            'nomor_sertifikat.unique' => 'Nomor sertifikat sudah dipakai oleh sertifikat lain.',
            'file_sertifikat.required' => 'File sertifikat (PDF) wajib dipilih.',
            'file_sertifikat.file' => 'File sertifikat gagal diunggah. Silakan coba lagi.',
            'file_sertifikat.mimes' => 'File sertifikat harus berformat PDF.',
            'file_sertifikat.max' => 'Ukuran file sertifikat maksimal 5 MB.',
        ]);

        $disk = Storage::disk(self::DISK);
        $pathLama = $penyelesaian->path_sertifikat;
        $unggahUlang = $pathLama !== null;

        // Nama file dibuat sistem (bukan dari nama asli) agar aman dan unik.
        $namaFile = 'sertifikat-'.$penyelesaian->id_penyelesaian.'-'.Str::uuid().'.pdf';
        $pathBaru = $request->file('file_sertifikat')->storeAs(self::FOLDER, $namaFile, self::DISK);
        if ($pathBaru === false) {
            return back()->withInput()->withErrors(['file_sertifikat' => 'File sertifikat gagal disimpan. Silakan coba lagi.'], 'sertifikat_'.$penyelesaian->id_penyelesaian);
        }

        try {
            DB::transaction(function () use ($penyelesaian, $data, $pathBaru, $pengunggah) {
                $penyelesaian->update([
                    'nomor_sertifikat' => trim($data['nomor_sertifikat']),
                    'path_sertifikat' => $pathBaru,
                    'id_pengunggah' => $pengunggah->id_pegawai,
                    'waktu_unggah' => now(),
                ]);
            });
        } catch (Throwable $e) {
            $disk->delete($pathBaru);   // basis data gagal → jangan tinggalkan file yatim
            throw $e;
        }

        // Unggah ulang: hapus file lama setelah file baru tersimpan.
        if ($unggahUlang && $pathLama !== $pathBaru) {
            $disk->delete($pathLama);
        }

        $penyelesaian->load('pesertaMagang.pengguna');
        if ($idPeserta = $penyelesaian->pesertaMagang?->id_pengguna) {
            Notifikasi::kirim(
                $idPeserta,
                'SERTIFIKAT_TERSEDIA',
                'Sertifikat magang Anda sudah tersedia. Unduh di halaman '.route('peserta.sertifikat.index').'.',
                'penyelesaian_magang',
                $penyelesaian->id_penyelesaian,
            );
        }

        Log::channel('aktivitas')->info($unggahUlang ? 'sertifikat_diunggah_ulang' : 'sertifikat_diunggah', [
            'id_penyelesaian' => $penyelesaian->id_penyelesaian,
            'nomor_sertifikat' => $penyelesaian->nomor_sertifikat,
            'oleh' => auth()->id(),
        ]);

        $nama = $penyelesaian->pesertaMagang?->pengguna?->nama_lengkap ?? 'peserta';

        return redirect()->route('superadmin.sertifikat.index', ['tab' => 'sudah'])
            ->with('sukses', ($unggahUlang ? 'Sertifikat berhasil diunggah ulang' : 'Sertifikat berhasil diunggah').' untuk '.$nama.'.');
    }
}
