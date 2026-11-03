<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `pegawai` — Pegawai DTI (Superadmin, Mentor, Kepala Seksi, Direktur). Semua pelaku pemeriksaan/keputusan menunjuk ke sini.
 */
class Pegawai extends Model
{
    protected $table = 'pegawai';

    protected $primaryKey = 'id_pegawai';

    public $timestamps = false;

    protected $fillable = [
        'id_pengguna',
        'id_seksi',
        'nip',
        'jabatan',
    ];

    protected function casts(): array
    {
        return [
            
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function seksi(): BelongsTo
    {
        return $this->belongsTo(Seksi::class, 'id_seksi', 'id_seksi');
    }

    public function pesertaBimbingan(): HasMany
    {
        return $this->hasMany(PesertaMagang::class, 'id_mentor', 'id_pegawai');
    }
}
