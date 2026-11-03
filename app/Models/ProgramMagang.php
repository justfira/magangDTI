<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `program_magang` — Program & periode rekrutmen jalur DTI (UC-02).
 */
class ProgramMagang extends Model
{
    protected $table = 'program_magang';

    protected $primaryKey = 'id_program';

    public const STATUS = ['Draft', 'Dipublikasikan', 'Ditutup'];

    protected $fillable = [
        'dibuat_oleh',
        'nama_program',
        'tanggal_buka',
        'tanggal_tutup',
        'informasi',
        'persyaratan',
        'status_publikasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_buka' => 'date',
            'tanggal_tutup' => 'date'
        ];
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'dibuat_oleh', 'id_pegawai');
    }

    public function syaratDokumenProgram(): HasMany
    {
        return $this->hasMany(SyaratDokumenProgram::class, 'id_program', 'id_program');
    }

    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class, 'id_program', 'id_program');
    }
}
