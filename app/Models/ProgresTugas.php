<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tabel `progres_tugas` — Satu progres = satu review mentor (UC-07).
 */
class ProgresTugas extends Model
{
    protected $table = 'progres_tugas';

    protected $primaryKey = 'id_progres';

    public $timestamps = false;
    public const HASIL_REVIEW = ['Perlu Perbaikan', 'Diterima'];

    public const FORMAT_BERKAS = ['pdf', 'zip', 'rar', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
    public const MAKS_UKURAN_BERKAS_KB = 10240; // 10 MB

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
            'urutan' => 'integer',
            'waktu_kirim' => 'datetime',
            'waktu_review' => 'datetime',
        ];
    }

    public function tugas(): BelongsTo
    {
        return $this->belongsTo(Tugas::class, 'id_tugas', 'id_tugas');
    }

    /**
     * Cek apakah progres diserahkan melewati batas tenggat tugas.
     */
    public function apakahTerlambat(): bool
    {
        if (! $this->waktu_kirim || ! $this->tugas?->tenggat) {
            return false;
        }

        return $this->waktu_kirim->isAfter($this->tugas->tenggat);
    }

    /**
     * Hitung selisih waktu keterlambatan pengumpulan.
     */
    public function selisihKeterlambatan(): ?string
    {
        if (! $this->apakahTerlambat()) {
            return null;
        }

        return $this->waktu_kirim->diffForHumans($this->tugas->tenggat, [
            'parts' => 2,
            'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
        ]);
    }
}
