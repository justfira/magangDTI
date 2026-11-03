<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `evaluasi_peserta` — Evaluasi akhir oleh mentor (UC-13).
 */
class EvaluasiPeserta extends Model
{
    protected $table = 'evaluasi_peserta';

    protected $primaryKey = 'id_evaluasi';

    public $timestamps = false;
    public const STATUS_DTI = ['Memenuhi', 'Belum Memenuhi'];
    public const STATUS_INSTITUSI = ['Belum Dinilai', 'Sudah Dinilai'];

    protected $fillable = [
        'id_peserta_magang',
        'status_evaluasi',
        'catatan',
        'path_dokumen_evaluasi',
        'waktu_evaluasi',
    ];

    protected function casts(): array
    {
        return [
            'waktu_evaluasi' => 'datetime'
        ];
    }

    public function pesertaMagang(): BelongsTo
    {
        return $this->belongsTo(PesertaMagang::class, 'id_peserta_magang', 'id_peserta_magang');
    }
}
