<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use App\Services\GagalUnggahSurat;
use App\Services\PermohonanInstitusiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * UC-04 Mencatat Permohonan Magang Institusi (FR-05) — Superadmin.
 * Tabel : pengajuan (jalur INSTITUSI), calon_peserta, dokumen_pengajuan
 *
 * Main flow : index (daftar + cari) -> create (form) -> store (validasi, simpan status "Diajukan", notifikasi sukses).
 * A1        : data wajib kosong / berkas tidak sesuai -> pesan per kolom + "Data belum lengkap atau berkas tidak sesuai".
 * E1        : gagal simpan -> "Gagal menyimpan permohonan, silakan coba lagi".
 * E2        : unggah gagal -> "Berkas gagal diunggah, silakan coba lagi".
 *
 * Permohonan yang diisi institusi sendiri lewat form online (/permohonan-institusi) juga tampil di sini,
 * ditandai sumber "Form online" (dicatat_oleh = NULL).
 */
class PermohonanInstitusiController extends Controller
{
    public function __construct(private PermohonanInstitusiService $layanan)
    {
    }

    /** Langkah 1–2: daftar permohonan institusi beserta status & nomor surat; cari institusi / nomor surat. */
    public function index(Request $request): View
    {
        $dasar = Pengajuan::where('jalur', 'INSTITUSI');

        $jumlahPerStatus = (clone $dasar)->selectRaw('status_pengajuan, COUNT(*) as jumlah')
            ->groupBy('status_pengajuan')->pluck('jumlah', 'status_pengajuan');
        $ringkasan = [
            'total' => (int) $jumlahPerStatus->sum(),
            'diajukan' => (int) ($jumlahPerStatus['Diajukan'] ?? 0),
            'online' => (clone $dasar)->whereNull('dicatat_oleh')->count(),
        ];

        $status = in_array($request->query('status'), Pengajuan::STATUS, true) ? $request->query('status') : null;
        $cari = trim((string) $request->query('cari'));

        $permohonan = (clone $dasar)
            ->withCount('calonPeserta')
            ->when($status, fn ($q) => $q->where('status_pengajuan', $status))
            ->when($request->query('sumber') === 'online', fn ($q) => $q->whereNull('dicatat_oleh'))
            ->when($request->query('sumber') === 'admin', fn ($q) => $q->whereNotNull('dicatat_oleh'))
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nama_institusi', 'like', "%{$cari}%")
                ->orWhere('nomor_surat', 'like', "%{$cari}%")
                ->orWhere('kode_pengajuan', 'like', "%{$cari}%")))
            ->orderByDesc('tanggal_pengajuan')->orderByDesc('id_pengajuan')
            ->paginate(10)->withQueryString();

        return view('superadmin.permohonan.index', compact('permohonan', 'ringkasan', 'jumlahPerStatus', 'status', 'cari'));
    }

    /** Langkah 3–4: form permohonan institusi. */
    public function create(): View
    {
        return view('superadmin.permohonan.form', [
            'maksPeserta' => PermohonanInstitusiService::MAKS_PESERTA,
            'maksKb' => PermohonanInstitusiService::MAKS_KB,
        ]);
    }

    /** Langkah 5–9: validasi -> simpan status "Diajukan" -> notifikasi "Permohonan berhasil dicatat". */
    public function store(Request $request): RedirectResponse
    {
        $validator = $this->layanan->validator($request);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()
                ->with('gagal', PermohonanInstitusiService::PESAN_TIDAK_VALID);   // A1
        }

        $pegawai = $this->pegawaiLogin();
        if (! $pegawai) {
            return back()->withInput()->with('gagal', 'Akun Anda belum terdaftar sebagai pegawai, hubungi Superadmin.');
        }

        try {
            $pengajuan = $this->layanan->simpan($validator->validated(), $request->file('surat'), $pegawai);
        } catch (GagalUnggahSurat) {
            return back()->withInput()->with('gagal', PermohonanInstitusiService::PESAN_GAGAL_UNGGAH);  // E2
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('gagal', PermohonanInstitusiService::PESAN_GAGAL_SIMPAN);  // E1
        }

        Log::channel('aktivitas')->info('permohonan_institusi_dicatat', [
            'kode' => $pengajuan->kode_pengajuan, 'institusi' => $pengajuan->nama_institusi, 'oleh' => auth()->id(),
        ]);

        return redirect()->route('superadmin.permohonan.index')
            ->with('sukses', PermohonanInstitusiService::PESAN_SUKSES." Kode: {$pengajuan->kode_pengajuan}.");
    }

    /** Detail satu permohonan: data institusi, tabel peserta, dan surat. */
    public function show(Pengajuan $pengajuan): View
    {
        abort_unless($pengajuan->jalur === 'INSTITUSI', 404);
        $pengajuan->load(['calonPeserta', 'dokumenPengajuan', 'dicatatOleh.pengguna', 'pemeriksa.pengguna']);

        return view('superadmin.permohonan.show', compact('pengajuan'));
    }

    /** Buka scan surat pengajuan (berkas disimpan di disk privat, hanya Superadmin yang bisa membuka). */
    public function surat(Pengajuan $pengajuan): StreamedResponse
    {
        abort_unless($pengajuan->jalur === 'INSTITUSI', 404);
        $dokumen = $pengajuan->dokumenPengajuan()
            ->where('jenis_dokumen', PermohonanInstitusiService::JENIS_DOKUMEN)
            ->latest('id_dokumen')->first();

        $disk = Storage::disk(PermohonanInstitusiService::DISK);
        abort_unless($dokumen && $disk->exists($dokumen->path_berkas), 404, 'Berkas surat tidak ditemukan.');

        return $disk->response($dokumen->path_berkas, $dokumen->nama_berkas);
    }
}
