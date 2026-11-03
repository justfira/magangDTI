<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `keputusan_penerimaan` — Rekomendasi Kepala Seksi + keputusan Direktur untuk satu calon (UC-05).
 */
class KeputusanPenerimaan extends Model
{
    protected $table = 'keputusan_penerimaan';

    protected $primaryKey = 'id_keputusan';

    public $timestamps = false;
    public const REKOMENDASI = ['Terima', 'Tolak'];
    public const KEPUTUSAN = ['Diterima', 'Ditolak'];

    protected $fillable = [
        'id_calon',
        'id_kepala_seksi',
        'id_direktur',
        'rekomendasi',
        'catatan_rekomendasi',
        'waktu_rekomendasi',
        'keputusan',
        'catatan_keputusan',
        'waktu_keputusan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_rekomendasi' => 'datetime',
            'waktu_keputusan' => 'datetime'
        ];
    }

    public function calon(): BelongsTo
    {
        return $this->belongsTo(CalonPeserta::class, 'id_calon', 'id_calon');
    }

    public function kepalaSeksi(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_kepala_seksi', 'id_pegawai');
    }

    public function direktur(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_direktur', 'id_pegawai');
    }

    public function pesertaMagang(): HasOne
    {
        return $this->hasOne(PesertaMagang::class, 'id_keputusan', 'id_keputusan');
    }
}
