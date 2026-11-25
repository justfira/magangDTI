<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'jumlah_progres',
        'judul_progres',
        'status',
        'nilai',
        'catatan_nilai',
    ];

    protected function casts(): array
    {
        return [
            'tenggat' => 'date',
            'jumlah_progres' => 'integer',
            'judul_progres' => 'array',
            'nilai' => 'integer',
        ];
    }

    public function pesertaMagang(): BelongsTo
    {
        return $this->belongsTo(PesertaMagang::class, 'id_peserta_magang', 'id_peserta_magang');
    }

    public function progresTugas(): HasMany
    {
        return $this->hasMany(ProgresTugas::class, 'id_tugas', 'id_tugas')->orderBy('urutan', 'asc');
    }

    public function progresTerakhir(): ?ProgresTugas
    {
        return $this->progresTugas()->orderByDesc('urutan')->first();
    }

    public function urutanProgresTerakhir(): int
    {
        return (int) ($this->progresTugas()->max('urutan') ?? 0);
    }

    public function urutanProgresBerikutnya(): int
    {
        return $this->urutanProgresTerakhir() + 1;
    }

    public function apakahBolehKirimProgres(): bool
    {
        return in_array($this->status, ['Ditugaskan', 'Perlu Perbaikan'], true);
    }

    /**
     * Kembalikan judul progres untuk urutan tertentu.
     * Jika tidak ada (mis. progres tambahan karena perbaikan), hasilkan label otomatis.
     */
    public function judulProgresKe(int $urutan): string
    {
        $daftar = $this->judul_progres ?? [];
        return !empty($daftar[$urutan - 1]) ? $daftar[$urutan - 1] : "Progres ke-{$urutan}";
    }
}
