<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\Logbook;
use App\Support\Notifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Periksa Logbook (UC-12)
 * Aktor  : Mentor DTI
 * Tabel  : logbook
 *
 * Status : IMPLEMENTASI — mentor hanya dapat memeriksa peserta bimbingannya
 *          (pastikanBimbingan). Hasil pemeriksaan terakhir menyimpan pada baris
 *          logbook yang sama (keputusan desain satu tabel, tanpa tabel riwayat).
 */
class PeriksaLogbookController extends Controller
{
    /**
     * Periksa Logbook — GET /mentor/logbook
     * Antrean logbook berstatus Dikirim + logbook bimbingan yang sudah diperiksa.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $pegawai = $this->pegawaiLogin();
        if (! $pegawai) {
            return redirect()->route('dashboard')->with('info', 'Halaman ini memerlukan akun mentor dengan data pegawai.');
        }

        $antrean = Logbook::whereHas('pesertaMagang', fn ($q) => $q->where('id_mentor', $pegawai->id_pegawai))
            ->where('status', 'Dikirim')
            ->with('pesertaMagang.pengguna')
            ->orderBy('waktu_kirim')
            ->get();

        $terdahulu = Logbook::whereHas('pesertaMagang', fn ($q) => $q->where('id_mentor', $pegawai->id_pegawai))
            ->whereIn('status', ['Disetujui', 'Perlu Perbaikan'])
            ->with('pesertaMagang.pengguna')
            ->orderByDesc('waktu_periksa')
            ->limit(30)
            ->get();

        return view('mentor.logbook.index', compact('antrean', 'terdahulu'));
    }

    /**
     * Detail Logbook Bimbingan — GET /mentor/logbook/{logbook}
     */
    public function show(Request $request, Logbook $logbook): View|RedirectResponse
    {
        $this->pastikanBimbingan($logbook->pesertaMagang);

        return view('mentor.logbook.show', ['logbook' => $logbook]);
    }

    /**
     * Simpan Pemeriksaan Logbook — POST /mentor/logbook/{logbook}/periksa
     * Disetujui / Perlu Perbaikan (+ catatan wajib), isi waktu_periksa; notifikasi ke peserta.
     */
    public function periksa(Request $request, Logbook $logbook): RedirectResponse
    {
        $this->pastikanBimbingan($logbook->pesertaMagang);

        if ($logbook->status !== 'Dikirim') {
            return redirect()->route('mentor.logbook.index')->with('info', 'Logbook ini sudah diperiksa sebelumnya.');
        }

        $data = $request->validate([
            'keputusan' => ['required', Rule::in(['Disetujui', 'Perlu Perbaikan'])],
            'catatan_mentor' => ['required_if:keputusan,Perlu Perbaikan', 'nullable', 'string', 'max:2000'],
        ], [
            'keputusan.required' => 'Keputusan pemeriksaan wajib dipilih.',
            'keputusan.in' => 'Keputusan pemeriksaan tidak valid.',
            'catatan_mentor.required_if' => 'Catatan wajib diisi apabila logbook perlu diperbaiki.',
            'catatan_mentor.max' => 'Catatan maksimal 2000 karakter.',
        ]);

        $logbook->update([
            'status' => $data['keputusan'],
            'catatan_mentor' => $data['catatan_mentor'] ?? null,
            'waktu_periksa' => now(),
        ]);

        Notifikasi::kirim(
            $logbook->pesertaMagang->id_pengguna,
            'LOGBOOK_DIPERIKSA',
            'Logbook tanggal '.$logbook->tanggal_kegiatan->translatedFormat('d M Y').' dinyatakan '.$logbook->status.' oleh mentor Anda.',
            'logbook',
            $logbook->id_logbook,
        );

        return redirect()->route('mentor.logbook.index')->with('sukses', 'Pemeriksaan logbook tersimpan.');
    }
}
