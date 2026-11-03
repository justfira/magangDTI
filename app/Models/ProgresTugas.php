<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `progres_tugas` — Satu progres = satu review mentor (UC-07).
 */
class ProgresTugas extends Model
{
    protected $table = 'progres_tugas';

    protected $primaryKey = 'id_progres';

    public $timestamps = false;
    public const HASIL_REVIEW = ['Perlu Perbaikan', 'Diterima'];

    protected $fillable = [
        'id_tugas',
        'urutan',
        'catatan_progres',
        'tautan_hasil',
        'path_berkas',
        'waktu_kirim',
        'hasil_review',
        'catatan_review',
        'waktu_review',
    ];

    protected function casts(): array
    {
        return [
            'waktu_kirim' => 'datetime',
            'waktu_review' => 'datetime'
        ];
    }

    public function tugas(): BelongsTo
    {
        return $this->belongsTo(Tugas::class, 'id_tugas', 'id_tugas');
    }
}
