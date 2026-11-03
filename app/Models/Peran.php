<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `peran` — Peran pengguna untuk hak akses (RBAC).
 */
class Peran extends Model
{
    protected $table = 'peran';

    protected $primaryKey = 'id_peran';

    public $timestamps = false;
    public const KODE = ['SUPERADMIN', 'MENTOR', 'KEPALA_SEKSI', 'DIREKTUR', 'PESERTA'];

    protected $fillable = [
        'kode_peran',
        'nama_peran',
    ];

    protected function casts(): array
    {
        return [
            
        ];
    }

    public function pengguna(): HasMany
    {
        return $this->hasMany(Pengguna::class, 'id_peran', 'id_peran');
    }
}
