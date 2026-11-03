<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `koreksi_presensi` — Koreksi presensi oleh Superadmin (UC-10).
 */
class KoreksiPresensi extends Model
{
    protected $table = 'koreksi_presensi';

    protected $primaryKey = 'id_koreksi';

    public $timestamps = false;

    protected $fillable = [
        'id_presensi',
        'id_pengoreksi',
        'alasan',
        'path_bukti',
        'data_sebelum',
        'data_sesudah',
        'waktu_koreksi',
    ];

    protected function casts(): array
    {
        return [
            'data_sebelum' => 'array',
            'data_sesudah' => 'array',
            'waktu_koreksi' => 'datetime'
        ];
    }

    public function presensi(): BelongsTo
    {
        return $this->belongsTo(Presensi::class, 'id_presensi', 'id_presensi');
    }

    public function pengoreksi(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_pengoreksi', 'id_pegawai');
    }
}
