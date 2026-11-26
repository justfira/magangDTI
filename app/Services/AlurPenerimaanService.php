<?php

namespace App\Services;

use App\Models\DokumenPengajuan;
use App\Models\Pengajuan;
use App\Models\Pengguna;
use App\Support\Notifikasi;
use Illuminate\Support\Collection;

/**
 * UC-05 Memverifikasi dan Memutuskan Penerimaan — helper bersama untuk tiga aktor:
 *   Superadmin (kelengkapan) -> Kepala Seksi (rekomendasi per calon) -> Direktur (keputusan per calon).
 *
 * Status pengajuan : Diajukan -> Perlu Perbaikan | Lengkap -> Selesai Diputuskan (saat SEMUA calon sudah diputuskan).
 * Rekomendasi & keputusan disimpan per calon di keputusan_penerimaan (satu permohonan institusi bisa banyak calon).
 */
class AlurPenerimaanService
{
    public const PESAN_GAGAL_SIMPAN = 'Gagal menyimpan, silakan coba lagi.';          // E1
    public const PESAN_BELUM_LENGKAP = 'Pengajuan belum dinyatakan lengkap.';          // A2
    public const PESAN_KOSONG = 'Data tidak tersedia.';                                // E3
    public const BANNER_DIREKTUR = 'Alur persetujuan Direktur merupakan usulan kelompok dan masih menunggu konfirmasi DTI.';

    /** Status yang masih boleh diperiksa Superadmin (Perlu Perbaikan boleh diperiksa ulang setelah diperbaiki). */
    public const STATUS_BISA_DIPERIKSA = ['Diajukan', 'Perlu Perbaikan'];

    public static function kelasStatus(?string $status): string
    {
        return match ($status) {
            'Diajukan', 'Menunggu' => 'status-peringatan',
            'Perlu Perbaikan', 'Ditolak', 'Tolak' => 'status-gagal',
            'Lengkap' => 'status-info',
            'Selesai Diputuskan', 'Diterima', 'Terima' => 'status-sukses',
            default => 'status-netral',
        };
    }

    /** Nama yang ditampilkan di daftar: nama pendaftar (DTI) atau nama institusi. */
    public static function namaPengajuan(Pengajuan $p): string
    {
        if ($p->jalur === 'INSTITUSI') {
            return (string) $p->nama_institusi;
        }

        return $p->calonPeserta->first()?->nama_lengkap ?? $p->diajukanOleh?->nama_lengkap ?? $p->kode_pengajuan;
    }

    public static function labelJalur(string $jalur): string
    {
        return $jalur === 'INSTITUSI' ? 'Institusi' : 'Rekrutmen DTI';
    }

    /**
     * Checklist dokumen persyaratan.
     * DTI       : satu baris per syarat program (berkas terbaru per syarat), syarat wajib tanpa berkas = "Belum diunggah".
     * INSTITUSI : scan surat pengajuan.
     * Berkas lain yang tidak terkait syarat tetap ditampilkan agar bisa diperiksa.
     *
     * @return Collection<int, array{nama:string, wajib:bool, dokumen:?DokumenPengajuan}>
     */
    public static function checklistDokumen(Pengajuan $p): Collection
    {
        $dokumen = $p->dokumenPengajuan->sortByDesc('id_dokumen');
        $baris = collect();
        $terpakai = [];

        if ($p->jalur === 'DTI' && $p->program) {
            foreach ($p->program->syaratDokumenProgram->sortBy('urutan') as $syarat) {
                $berkas = $dokumen->firstWhere('id_syarat', $syarat->id_syarat);
                if ($berkas) {
                    $terpakai[] = $berkas->id_dokumen;
                }
                $baris->push(['nama' => $syarat->nama_syarat, 'wajib' => (bool) $syarat->wajib, 'dokumen' => $berkas]);
            }
        }

        // Berkas yang tidak tertaut ke syarat (mis. SURAT_PENGAJUAN jalur institusi). Ambil yang terbaru per jenis.
        $dokumen->reject(fn ($d) => in_array($d->id_dokumen, $terpakai, true) || ($d->id_syarat && $p->jalur === 'DTI'))
            ->unique('jenis_dokumen')
            ->each(fn ($d) => $baris->push([
                'nama' => self::labelJenisDokumen($d->jenis_dokumen),
                'wajib' => $p->jalur === 'INSTITUSI',
                'dokumen' => $d,
            ]));

        return $baris->values();
    }

    public static function labelJenisDokumen(string $jenis): string
    {
        return match ($jenis) {
            'SURAT_PENGAJUAN' => 'Surat pengajuan institusi',
            default => ucwords(strtolower(str_replace('_', ' ', $jenis))),
        };
    }

    /**
     * Langkah alur persetujuan untuk timeline (Diajukan -> Pemeriksaan -> Rekomendasi -> Keputusan).
     *
     * @param  bool  $publik  true = untuk halaman publik institusi: tanpa nama pegawai & tanpa isi rekomendasi internal.
     * @return array<int, array{judul:string, ket:string, keadaan:string}>  keadaan: selesai | aktif | gagal | menunggu
     */
    public static function langkahAlur(Pengajuan $p, bool $publik = false): array
    {
        $calon = $p->calonPeserta;
        $keputusan = $calon->map(fn ($c) => $c->keputusanPenerimaan)->filter();
        $jumlah = max(1, $calon->count());
        $sudahDirekomendasi = $keputusan->count();
        $sudahDiputuskan = $keputusan->whereNotNull('keputusan')->count();
        $tgl = fn ($w) => $w?->translatedFormat('d M Y, H:i');

        $periksa = match ($p->status_pengajuan) {
            'Diajukan' => ['ket' => $p->waktu_pemeriksaan ? 'Diajukan ulang, menunggu pemeriksaan' : 'Menunggu pemeriksaan', 'keadaan' => 'aktif'],
            'Perlu Perbaikan' => ['ket' => 'Perlu perbaikan · '.$tgl($p->waktu_pemeriksaan), 'keadaan' => 'gagal'],
            default => ['ket' => 'Lengkap · '.$tgl($p->waktu_pemeriksaan).(! $publik && $p->pemeriksa?->pengguna ? ' · '.$p->pemeriksa->pengguna->nama_lengkap : ''), 'keadaan' => 'selesai'],
        };
        $sudahLengkap = in_array($p->status_pengajuan, ['Lengkap', 'Selesai Diputuskan'], true);

        $rekom = ! $sudahLengkap
            ? ['ket' => 'Menunggu', 'keadaan' => 'menunggu']
            : ($sudahDirekomendasi >= $jumlah
                ? ['ket' => ($jumlah > 1 || $publik) ? ($publik ? 'Selesai' : "{$sudahDirekomendasi} dari {$jumlah} calon direkomendasikan") : 'Rekomendasi: '.$keputusan->first()->rekomendasi.' · '.$tgl($keputusan->first()->waktu_rekomendasi), 'keadaan' => 'selesai']
                : ['ket' => $publik ? 'Sedang ditinjau' : "{$sudahDirekomendasi} dari {$jumlah} calon direkomendasikan", 'keadaan' => 'aktif']);

        $putus = $sudahDiputuskan >= $jumlah && $sudahDirekomendasi >= $jumlah
            ? ['ket' => $jumlah > 1 ? "Semua {$jumlah} calon sudah diputuskan" : 'Keputusan: '.$keputusan->first()->keputusan.' · '.$tgl($keputusan->first()->waktu_keputusan), 'keadaan' => 'selesai']
            : ($sudahDirekomendasi > 0
                ? ['ket' => "{$sudahDiputuskan} dari {$jumlah} calon diputuskan", 'keadaan' => 'aktif']
                : ['ket' => 'Menunggu', 'keadaan' => 'menunggu']);

        return [
            ['judul' => 'Diajukan', 'ket' => $tgl($p->tanggal_pengajuan).' · '.self::labelJalur($p->jalur), 'keadaan' => 'selesai'],
            ['judul' => $publik ? 'Pemeriksaan berkas oleh DTI' : 'Pemeriksaan Superadmin'] + $periksa,
            ['judul' => $publik ? 'Peninjauan Kepala Seksi' : 'Rekomendasi Kepala Seksi'] + $rekom,
            ['judul' => 'Keputusan Direktur'] + $putus,
        ];
    }

    /** Kirim notifikasi ke semua pengguna aktif dengan peran tertentu. */
    public static function beritahuPeran(string $kodePeran, string $jenis, string $pesan, string $entitas, int $idEntitas): void
    {
        Pengguna::where('status_akun', 'Aktif')
            ->whereHas('peran', fn ($q) => $q->where('kode_peran', $kodePeran))
            ->pluck('id_pengguna')
            ->each(fn ($id) => Notifikasi::kirim($id, $jenis, $pesan, $entitas, $idEntitas));
    }

    /** Setelah keputusan Direktur: pengajuan jadi "Selesai Diputuskan" bila semua calon sudah diputuskan. */
    public static function tutupJikaSemuaDiputuskan(Pengajuan $p): bool
    {
        $belum = $p->calonPeserta()
            ->where(fn ($q) => $q->whereDoesntHave('keputusanPenerimaan')
                ->orWhereHas('keputusanPenerimaan', fn ($k) => $k->whereNull('keputusan')))
            ->exists();

        if (! $belum && $p->status_pengajuan !== 'Selesai Diputuskan') {
            $p->update(['status_pengajuan' => 'Selesai Diputuskan']);

            return true;
        }

        return false;
    }

}
