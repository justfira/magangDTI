<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `syarat_dokumen_program` — Daftar berkas yang diminta per program.
 */
class SyaratDokumenProgram extends Model
{
    protected $table = 'syarat_dokumen_program';

    protected $primaryKey = 'id_syarat';

    public $timestamps = false;

    protected $fillable = [
        'id_program',
        'nama_syarat',
        'wajib',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'wajib' => 'boolean'
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ProgramMagang::class, 'id_program', 'id_program');
    }

    public function dokumenPengajuan(): HasMany
    {
        return $this->hasMany(DokumenPengajuan::class, 'id_syarat', 'id_syarat');
    }
}
