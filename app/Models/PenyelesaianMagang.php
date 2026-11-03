<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `penyelesaian_magang` — Rekomendasi Kasi + keputusan Direktur + sertifikat (UC-14, UC-15).
 */
class PenyelesaianMagang extends Model
{
    protected $table = 'penyelesaian_magang';

    protected $primaryKey = 'id_penyelesaian';

    public $timestamps = false;
    public const REKOMENDASI = ['Layak Diselesaikan', 'Perlu Tindak Lanjut'];
    public const KEPUTUSAN = ['Disetujui', 'Ditolak'];

    protected $fillable = [
        'id_peserta_magang',
        'id_kepala_seksi',
        'id_direktur',
        'id_pengunggah',
        'rekomendasi',
        'catatan_rekomendasi',
        'waktu_rekomendasi',
        'keputusan',
        'catatan_keputusan',
        'waktu_keputusan',
        'nomor_sertifikat',
        'path_sertifikat',
        'waktu_unggah',
    ];

    protected function casts(): array
    {
        return [
            'waktu_rekomendasi' => 'datetime',
            'waktu_keputusan' => 'datetime',
            'waktu_unggah' => 'datetime'
        ];
    }

    public function pesertaMagang(): BelongsTo
    {
        return $this->belongsTo(PesertaMagang::class, 'id_peserta_magang', 'id_peserta_magang');
    }

    public function kepalaSeksi(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_kepala_seksi', 'id_pegawai');
    }

    public function direktur(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_direktur', 'id_pegawai');
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_pengunggah', 'id_pegawai');
    }
}
