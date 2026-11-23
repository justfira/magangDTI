<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `dokumen_pengajuan` — Berkas yang diunggah; tiap berkas diperiksa sekali (hasil_pemeriksaan).
 */
class DokumenPengajuan extends Model
{
    protected $table = 'dokumen_pengajuan';

    protected $primaryKey = 'id_dokumen';

    public $timestamps = false;
    public const HASIL = ['Sesuai', 'Tidak Sesuai'];

    public const MAKS_UKURAN_KB = 2048;
    public const MIMES_DOKUMEN = 'pdf';
    public const MIMES_FOTO = 'jpg,jpeg,png';

    /**
     * Tentukan apakah syarat ini termasuk foto/pas foto.
     */
    public static function isSyaratFoto(string $namaSyarat): bool
    {
        return (bool) preg_match('/foto/i', $namaSyarat);
    }

    /**
     * Dapatkan aturan mimes berdasarkan nama syarat dokumen.
     */
    public static function getMimesUntukSyarat(string $namaSyarat): string
    {
        return self::isSyaratFoto($namaSyarat) ? self::MIMES_FOTO : self::MIMES_DOKUMEN;
    }

    /**
     * Format teks deskripsi tipe & ukuran berkas untuk tampilan label.
     */
    public static function getDeskripsiFormat(string $namaSyarat): string
    {
        return self::isSyaratFoto($namaSyarat) ? 'JPG, JPEG, atau PNG (maks. 2 MB)' : 'PDF (maks. 2 MB)';
    }

    protected $fillable = [
        'id_pengajuan',
        'id_syarat',
        'jenis_dokumen',
        'nama_berkas',
        'path_berkas',
        'ukuran_bytes',
        'uploaded_at',
        'hasil_pemeriksaan',
        'catatan_pemeriksaan',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime'
        ];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class, 'id_pengajuan', 'id_pengajuan');
    }

    public function syarat(): BelongsTo
    {
        return $this->belongsTo(SyaratDokumenProgram::class, 'id_syarat', 'id_syarat');
    }
}
