<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `seksi` — Seksi di DTI.
 */
class Seksi extends Model
{
    protected $table = 'seksi';

    protected $primaryKey = 'id_seksi';

    public $timestamps = false;

    protected $fillable = [
        'nama_seksi',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            
        ];
    }

    public function pegawai(): HasMany
    {
        return $this->hasMany(Pegawai::class, 'id_seksi', 'id_seksi');
    }

    public function pesertaMagang(): HasMany
    {
        return $this->hasMany(PesertaMagang::class, 'id_seksi', 'id_seksi');
    }
}
