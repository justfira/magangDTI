<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `notifikasi` — Isi kotak notifikasi (ikon lonceng).
 */
class Notifikasi extends Model
{
    protected $table = 'notifikasi';

    protected $primaryKey = 'id_notifikasi';

    const UPDATED_AT = null;

    protected $fillable = [
        'id_pengguna',
        'jenis_entitas',
        'id_entitas',
        'jenis',
        'pesan',
        'sudah_dibaca',
    ];

    protected function casts(): array
    {
        return [
            'sudah_dibaca' => 'boolean'
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }
}
