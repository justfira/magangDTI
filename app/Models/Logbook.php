<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `logbook` — Kegiatan harian peserta, diperiksa mentor (UC-12).
 */
class Logbook extends Model
{
    protected $table = 'logbook';

    protected $primaryKey = 'id_logbook';

    public $timestamps = false;
    public const STATUS = ['Dikirim', 'Disetujui', 'Perlu Perbaikan'];

    protected $fillable = [
        'id_peserta_magang',
        'tanggal_kegiatan',
        'uraian_aktivitas',
        'status',
        'catatan_mentor',
        'waktu_kirim',
        'waktu_periksa',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kegiatan' => 'date',
            'waktu_kirim' => 'datetime',
            'waktu_periksa' => 'datetime'
        ];
    }

    public function pesertaMagang(): BelongsTo
    {
        return $this->belongsTo(PesertaMagang::class, 'id_peserta_magang', 'id_peserta_magang');
    }
}
