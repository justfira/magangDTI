<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `presensi` — Kehadiran sah, satu baris per peserta per hari (UC-08, UC-10).
 */
class Presensi extends Model
{
    protected $table = 'presensi';

    protected $primaryKey = 'id_presensi';

    public $timestamps = false;
    public const SUMBER = ['REGULER', 'SUSULAN', 'KOREKSI'];

    protected $fillable = [
        'id_peserta_magang',
        'id_pengajuan_kehadiran',
        'tanggal',
        'sumber',
        'waktu_checkin',
        'lat_checkin',
        'long_checkin',
        'akurasi_checkin',
        'waktu_checkout',
        'lat_checkout',
        'long_checkout',
        'akurasi_checkout',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'waktu_checkin' => 'datetime',
            'lat_checkin' => 'decimal:7',
            'long_checkin' => 'decimal:7',
            'akurasi_checkin' => 'decimal:2',
            'waktu_checkout' => 'datetime',
            'lat_checkout' => 'decimal:7',
            'long_checkout' => 'decimal:7',
            'akurasi_checkout' => 'decimal:2'
        ];
    }

    public function pesertaMagang(): BelongsTo
    {
        return $this->belongsTo(PesertaMagang::class, 'id_peserta_magang', 'id_peserta_magang');
    }

    public function pengajuanKehadiran(): BelongsTo
    {
        return $this->belongsTo(PengajuanKehadiran::class, 'id_pengajuan_kehadiran', 'id_pengajuan_kehadiran');
    }

    public function koreksiPresensi(): HasMany
    {
        return $this->hasMany(KoreksiPresensi::class, 'id_presensi', 'id_presensi');
    }
}
