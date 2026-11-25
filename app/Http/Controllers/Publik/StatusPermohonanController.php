<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use App\Services\AlurPenerimaanService as Alur;
use App\Services\GagalUnggahSurat;
use App\Services\PermohonanInstitusiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Cek status & kirim perbaikan permohonan institusi (tanpa akun) — lanjutan UC-04/UC-05 A1.
 *
 * Akses: kode permohonan + email narahubung. Kode berurutan (INS-2026-0001, ...) sehingga mudah ditebak,
 * jadi email wajib cocok juga. Setelah cocok, id permohonan disimpan di session (bukan di URL),
 * dan percobaan dibatasi throttle di route.
 *
 * Perbaikan hanya bisa dikirim saat status "Perlu Perbaikan"; setelah dikirim status kembali "Diajukan"
 * dan Superadmin diberi notifikasi untuk memeriksa ulang (UC-05).
 */
class StatusPermohonanController extends Controller
{
    private const SESI = 'permohonan_institusi_akses';
    public const PESAN_TIDAK_COCOK = 'Kode permohonan atau email narahubung tidak cocok.';

    public function __construct(private PermohonanInstitusiService $layanan)
    {
    }

    /** Form cek status: kode + email. */
    public function cek(Request $request): View
    {
        return view('publik.permohonan-cek', [
            'kodeAwal' => strtoupper(substr((string) $request->query('kode'), 0, 30)),
        ]);
    }

    public function masuk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:60'],
        ], [
            'kode.required' => 'Kode permohonan wajib diisi.',
            'email.required' => 'Email narahubung wajib diisi.',
            'email.email' => 'Email narahubung harus berupa alamat email yang valid.',
        ]);

        $pengajuan = $this->layanan->cariUntukInstitusi($data['kode'], $data['email']);
        if (! $pengajuan) {
            // Pesan sama untuk kode salah maupun email salah, supaya tidak membocorkan kode yang ada.
            return back()->withInput($request->only('kode', 'email'))->withErrors(['kode' => self::PESAN_TIDAK_COCOK]);
        }

        $request->session()->regenerate();
        $request->session()->put(self::SESI, $pengajuan->id_pengajuan);

        return redirect()->route('publik.permohonan.status');
    }

    public function keluar(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESI);

        return redirect()->route('publik.permohonan.cek')->with('sukses', 'Anda sudah keluar dari halaman status permohonan.');
    }

    public function status(Request $request): View|RedirectResponse
    {
        $pengajuan = $this->permohonanSesi($request);
        if (! $pengajuan) {
            return redirect()->route('publik.permohonan.cek');
        }

        $pengajuan->load(['calonPeserta.keputusanPenerimaan', 'dokumenPengajuan']);

        return view('publik.permohonan-status', [
            'pengajuan' => $pengajuan,
            'langkah' => Alur::langkahAlur($pengajuan, publik: true),
            'tidakSesuai' => $pengajuan->dokumenPengajuan->where('hasil_pemeriksaan', 'Tidak Sesuai')
                ->sortByDesc('id_dokumen')->unique('jenis_dokumen'),
            'kontak' => $this->layanan->pecahKontak($pengajuan->kontak_institusi),
        ]);
    }

    public function edit(Request $request): View|RedirectResponse
    {
        $pengajuan = $this->permohonanSesi($request);
        if (! $pengajuan) {
            return redirect()->route('publik.permohonan.cek');
        }
        if ($pengajuan->status_pengajuan !== 'Perlu Perbaikan') {
            return redirect()->route('publik.permohonan.status')->with('gagal', 'Permohonan ini sedang tidak memerlukan perbaikan.');
        }

        $pengajuan->load('calonPeserta');

        return view('publik.permohonan-perbaikan', [
            'pengajuan' => $pengajuan,
            'kontak' => $this->layanan->pecahKontak($pengajuan->kontak_institusi),
            'pesertaAwal' => $pengajuan->calonPeserta->map->only(PermohonanInstitusiService::KOLOM_PESERTA)->values()->all(),
            'suratWajib' => $this->layanan->suratWajibDiganti($pengajuan),
            'maksPeserta' => PermohonanInstitusiService::MAKS_PESERTA,
            'maksKb' => PermohonanInstitusiService::MAKS_KB,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $pengajuan = $this->permohonanSesi($request);
        if (! $pengajuan) {
            return redirect()->route('publik.permohonan.cek');
        }
        if ($pengajuan->status_pengajuan !== 'Perlu Perbaikan') {
            return redirect()->route('publik.permohonan.status')->with('gagal', 'Permohonan ini sedang tidak memerlukan perbaikan.');
        }

        // Nama institusi dikunci: selalu pakai nilai tersimpan, abaikan isian dari browser.
        $request->merge(['nama_institusi' => $pengajuan->nama_institusi]);

        $suratWajib = $this->layanan->suratWajibDiganti($pengajuan);
        $validator = $this->layanan->validator($request, [
            'surat' => [$suratWajib ? 'required' : 'nullable', 'file',
                'mimes:'.implode(',', PermohonanInstitusiService::FORMAT_SURAT), 'max:'.PermohonanInstitusiService::MAKS_KB],
        ], [
            'surat.required' => 'Surat sebelumnya ditandai Tidak Sesuai — unggah surat yang sudah diperbaiki.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('publik.permohonan.perbaikan')->withErrors($validator)->withInput()
                ->with('gagal', PermohonanInstitusiService::PESAN_TIDAK_VALID);
        }

        try {
            $this->layanan->perbaiki($pengajuan, $validator->validated(), $request->file('surat'));
        } catch (GagalUnggahSurat) {
            return redirect()->route('publik.permohonan.perbaikan')->withInput()->with('gagal', PermohonanInstitusiService::PESAN_GAGAL_UNGGAH);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('publik.permohonan.perbaikan')->withInput()->with('gagal', PermohonanInstitusiService::PESAN_GAGAL_SIMPAN);
        }

        Log::channel('aktivitas')->info('perbaikan_permohonan_institusi', [
            'kode' => $pengajuan->kode_pengajuan, 'ip' => $request->ip(),
        ]);

        return redirect()->route('publik.permohonan.status')
            ->with('sukses', 'Perbaikan terkirim. Status permohonan kembali menjadi Diajukan dan akan diperiksa ulang oleh DTI.');
    }

    private function permohonanSesi(Request $request): ?Pengajuan
    {
        $id = $request->session()->get(self::SESI);

        return $id ? Pengajuan::where('jalur', 'INSTITUSI')->find($id) : null;
    }
}
