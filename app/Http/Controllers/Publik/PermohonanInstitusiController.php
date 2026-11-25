<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Services\GagalUnggahSurat;
use App\Services\PermohonanInstitusiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * UC-04 sisi publik — Landing page + form online permohonan magang institusi (tanpa login, seperti Google Form).
 *
 * Institusi (kampus/sekolah) mengisi data institusi, daftar peserta, dan mengunggah scan surat pengajuan.
 * Hasilnya masuk ke tabel yang sama dengan pencatatan Superadmin (pengajuan jalur INSTITUSI, status "Diajukan",
 * dicatat_oleh = NULL) lalu Superadmin mendapat notifikasi dan memprosesnya lewat UC-05.
 *
 * Pengamanan: rate limit di route (throttle) + honeypot "situs_web" untuk menahan bot spam.
 */
class PermohonanInstitusiController extends Controller
{
    public function __construct(private PermohonanInstitusiService $layanan)
    {
    }

    public function create(): View
    {
        return view('publik.permohonan-institusi', [
            'maksPeserta' => PermohonanInstitusiService::MAKS_PESERTA,
            'maksKb' => PermohonanInstitusiService::MAKS_KB,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Honeypot: kolom tersembunyi ini hanya diisi bot. Pura-pura berhasil tanpa menyimpan apa pun.
        if (filled($request->input('situs_web'))) {
            return redirect()->route('publik.permohonan.create');
        }

        $validator = $this->layanan->validator(
            $request,
            ['pernyataan' => ['accepted']],
            ['pernyataan.accepted' => 'Centang pernyataan bahwa data yang diisi sudah benar.'],
        );

        if ($validator->fails()) {
            return $this->kembaliKeForm()->withErrors($validator)
                ->with('gagal', PermohonanInstitusiService::PESAN_TIDAK_VALID);
        }

        try {
            $data = $validator->validated();
            $pengajuan = $this->layanan->simpan($data, $request->file('surat'), null);
        } catch (GagalUnggahSurat) {
            return $this->kembaliKeForm()->with('gagal', PermohonanInstitusiService::PESAN_GAGAL_UNGGAH);
        } catch (Throwable $e) {
            report($e);

            return $this->kembaliKeForm()->with('gagal', PermohonanInstitusiService::PESAN_GAGAL_SIMPAN);
        }

        Log::channel('aktivitas')->info('permohonan_institusi_online', [
            'kode' => $pengajuan->kode_pengajuan, 'institusi' => $pengajuan->nama_institusi, 'ip' => $request->ip(),
        ]);

        return redirect()->route('publik.permohonan.terkirim')->with('permohonan_terkirim', [
            'kode' => $pengajuan->kode_pengajuan,
            'institusi' => $pengajuan->nama_institusi,
            'nomor_surat' => $pengajuan->nomor_surat,
            'jumlah_peserta' => count($data['peserta']),
            'email' => $data['kontak_email'],
        ]);
    }

    /** Kembali ke form (langsung ke bagian #formulir) dengan isian yang sudah diketik tetap terisi. */
    private function kembaliKeForm(): RedirectResponse
    {
        return redirect()->to(route('publik.permohonan.create').'#formulir')->withInput();
    }

    /** Halaman konfirmasi setelah kirim. Hanya bisa dibuka tepat setelah berhasil mengirim. */
    public function terkirim(Request $request): View|RedirectResponse
    {
        $ringkas = $request->session()->get('permohonan_terkirim');
        if (! $ringkas) {
            return redirect()->route('publik.permohonan.create');
        }

        return view('publik.permohonan-terkirim', compact('ringkas'));
    }
}
