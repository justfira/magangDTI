<?php

namespace App\Http\Controllers\Direktur;

use App\Http\Controllers\Controller;
use App\Models\PenyelesaianMagang;
use App\Models\Pengguna;
use App\Support\Notifikasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Persetujuan Penyelesaian (UC-14) — SUDAH DIIMPLEMENTASIKAN.
 * Aktor  : Direktur DTI
 * Tabel  : penyelesaian_magang (tulis), peserta_magang (status_magang = Selesai bila disetujui)
 *
 * Aturan:
 *  - Hanya rekomendasi 'Layak Diselesaikan' yang belum diputuskan yang masuk antrean & boleh diputuskan.
 *  - Catatan wajib bila Ditolak, opsional bila Disetujui.
 *  - Disetujui: notifikasi Superadmin aktif (unggah sertifikat), Kasi pengusul, peserta.
 *    Ditolak  : notifikasi Kasi pengusul dan peserta.
 */
class KeputusanPenyelesaianController extends Controller
{
    private const TAB = ['menunggu' => 'Menunggu Keputusan', 'riwayat' => 'Riwayat'];

    /**
     * Persetujuan Penyelesaian — GET /direktur/keputusan-penyelesaian
     * Tab Menunggu Keputusan (dengan form per baris) dan Riwayat (dengan status sertifikat).
     */
    public function index(Request $request): View
    {
        $tab = array_key_exists($request->query('tab'), self::TAB) ? $request->query('tab') : 'menunggu';

        $penyelesaian = PenyelesaianMagang::query()
            ->with(['pesertaMagang.pengguna', 'pesertaMagang.seksi', 'pesertaMagang.mentor.pengguna', 'kepalaSeksi.pengguna', 'direktur.pengguna'])
            ->when($tab === 'menunggu', fn (Builder $q) => $q->where('rekomendasi', 'Layak Diselesaikan')->whereNull('keputusan')->orderBy('waktu_rekomendasi'))
            ->when($tab === 'riwayat', fn (Builder $q) => $q->whereNotNull('keputusan')->orderByDesc('waktu_keputusan'))
            ->paginate(10)
            ->withQueryString();

        $jumlahMenunggu = PenyelesaianMagang::where('rekomendasi', 'Layak Diselesaikan')->whereNull('keputusan')->count();

        return view('direktur.penyelesaian.index', [
            'penyelesaian' => $penyelesaian,
            'tab' => $tab,
            'daftarTab' => self::TAB,
            'jumlahMenunggu' => $jumlahMenunggu,
        ]);
    }

    /**
     * Simpan Keputusan Penyelesaian — PUT /direktur/keputusan-penyelesaian/{penyelesaian}
     * Disetujui / Ditolak; jika Disetujui ubah peserta_magang.status_magang = Selesai; kirim notifikasi.
     */
    public function update(Request $request, PenyelesaianMagang $penyelesaian): RedirectResponse
    {
        abort_unless($penyelesaian->rekomendasi === 'Layak Diselesaikan', 403, 'Rekomendasi ini tidak diajukan untuk diputuskan Direktur.');

        $direktur = $this->pegawaiLogin();
        abort_if($direktur === null, 403, 'Akun Anda tidak terdaftar sebagai pegawai.');

        // Error bag per baris agar pesan & isian lama hanya muncul di baris yang dikirim.
        $data = $request->validateWithBag('keputusan_'.$penyelesaian->id_penyelesaian, [
            'keputusan' => ['required', Rule::in(PenyelesaianMagang::KEPUTUSAN)],
            'catatan_keputusan' => ['nullable', 'required_if:keputusan,Ditolak', 'string', 'max:2000'],
        ], [
            'keputusan.required' => 'Pilih keputusan penyelesaian.',
            'keputusan.in' => 'Keputusan tidak valid.',
            'catatan_keputusan.required_if' => 'Catatan wajib diisi bila keputusan Ditolak.',
            'catatan_keputusan.max' => 'Catatan keputusan maksimal 2000 karakter.',
        ]);

        $berhasil = DB::transaction(function () use ($data, $penyelesaian, $direktur) {
            // Kunci baris agar dua keputusan bersamaan tidak saling menimpa.
            $terkunci = PenyelesaianMagang::whereKey($penyelesaian->id_penyelesaian)->lockForUpdate()->firstOrFail();
            if ($terkunci->keputusan !== null) {
                return false;
            }

            $terkunci->update([
                'keputusan' => $data['keputusan'],
                'catatan_keputusan' => $data['catatan_keputusan'] ?? null,
                'id_direktur' => $direktur->id_pegawai,
                'waktu_keputusan' => now(),
            ]);

            if ($data['keputusan'] === 'Disetujui') {
                $terkunci->pesertaMagang()->update(['status_magang' => 'Selesai']);
            }

            return true;
        });

        if (! $berhasil) {
            return redirect()->route('direktur.penyelesaian.index')
                ->withErrors(['keputusan' => 'Rekomendasi ini sudah diputuskan sebelumnya.']);
        }

        $penyelesaian->refresh()->load(['pesertaMagang.pengguna', 'kepalaSeksi']);
        $this->kirimNotifikasi($penyelesaian);

        Log::channel('aktivitas')->info('penyelesaian_diputuskan', [
            'id_penyelesaian' => $penyelesaian->id_penyelesaian,
            'id_peserta_magang' => $penyelesaian->id_peserta_magang,
            'keputusan' => $penyelesaian->keputusan,
            'oleh' => auth()->id(),
        ]);

        $nama = $penyelesaian->pesertaMagang?->pengguna?->nama_lengkap ?? 'peserta';

        return redirect()->route('direktur.penyelesaian.index')
            ->with('sukses', "Keputusan \"{$penyelesaian->keputusan}\" untuk {$nama} berhasil disimpan.");
    }

    /** Notifikasi sesuai keputusan: Disetujui → Superadmin aktif, Kasi, peserta; Ditolak → Kasi, peserta. */
    private function kirimNotifikasi(PenyelesaianMagang $penyelesaian): void
    {
        $id = $penyelesaian->id_penyelesaian;
        $nama = $penyelesaian->pesertaMagang?->pengguna?->nama_lengkap ?? 'peserta';
        $disetujui = $penyelesaian->keputusan === 'Disetujui';

        if ($disetujui) {
            $this->superadminAktif()->each(fn (Pengguna $admin) => Notifikasi::kirim(
                $admin->id_pengguna, 'UNGGAH_SERTIFIKAT', "Unggah sertifikat untuk {$nama}.", 'penyelesaian_magang', $id,
            ));
        }

        if ($idKasi = $penyelesaian->kepalaSeksi?->id_pengguna) {
            Notifikasi::kirim(
                $idKasi,
                'KEPUTUSAN_PENYELESAIAN',
                $disetujui
                    ? "Rekomendasi penyelesaian magang {$nama} disetujui Direktur."
                    : "Rekomendasi penyelesaian magang {$nama} ditolak Direktur. Catatan: {$penyelesaian->catatan_keputusan}",
                'penyelesaian_magang',
                $id,
            );
        }

        if ($idPeserta = $penyelesaian->pesertaMagang?->id_pengguna) {
            Notifikasi::kirim(
                $idPeserta,
                'KEPUTUSAN_PENYELESAIAN',
                $disetujui
                    ? 'Penyelesaian magang Anda disetujui. Sertifikat akan tersedia di halaman '.route('peserta.sertifikat.index').' setelah diunggah.'
                    : "Penyelesaian magang Anda belum disetujui. Catatan Direktur: {$penyelesaian->catatan_keputusan}",
                'penyelesaian_magang',
                $id,
            );
        }
    }

    /** Semua akun Superadmin yang aktif. */
    private function superadminAktif()
    {
        return Pengguna::where('status_akun', 'Aktif')
            ->whereHas('peran', fn (Builder $q) => $q->where('kode_peran', 'SUPERADMIN'))
            ->get();
    }
}
