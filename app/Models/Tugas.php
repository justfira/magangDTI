<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `tugas` — Tugas perorangan dari mentor peserta (UC-07).
 */
class Tugas extends Model
{
    protected $table = 'tugas';

    protected $primaryKey = 'id_tugas';

    const UPDATED_AT = null;
    public const STATUS = ['Ditugaskan', 'Diserahkan', 'Perlu Perbaikan', 'Selesai'];

    protected $fillable = [
        'id_peserta_magang',
        'judul',
        'deskripsi',
        'tenggat',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tenggat' => 'date'
        ];
    }

    public function pesertaMagang(): BelongsTo
    {
        return $this->belongsTo(PesertaMagang::class, 'id_peserta_magang', 'id_peserta_magang');
    }

    public function progresTugas(): HasMany
    {
        return $this->hasMany(ProgresTugas::class, 'id_tugas', 'id_tugas');
    }
}
