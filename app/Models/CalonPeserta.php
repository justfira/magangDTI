<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `calon_peserta` — Orang yang diajukan dalam satu pengajuan.
 */
class CalonPeserta extends Model
{
    protected $table = 'calon_peserta';

    protected $primaryKey = 'id_calon';

    public $timestamps = false;

    protected $fillable = [
        'id_pengajuan',
        'nama_lengkap',
        'email',
        'no_hp',
        'nomor_induk_pendidikan',
        'institusi_asal',
        'jurusan_prodi',
    ];

    protected function casts(): array
    {
        return [
            
        ];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class, 'id_pengajuan', 'id_pengajuan');
    }

    public function keputusanPenerimaan(): HasOne
    {
        return $this->hasOne(KeputusanPenerimaan::class, 'id_calon', 'id_calon');
    }
}
