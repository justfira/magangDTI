<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `peserta_magang` — Hanya dibuat dari keputusan penerimaan. Pusat semua kegiatan magang (UC-06, UC-17).
 */
class PesertaMagang extends Model
{
    protected $table = 'peserta_magang';

    protected $primaryKey = 'id_peserta_magang';

    const UPDATED_AT = null;
    public const STATUS = ['Belum Mulai', 'Aktif', 'Selesai', 'Dihentikan'];

    protected $fillable = [
        'id_keputusan',
        'id_pengguna',
        'id_seksi',
        'id_mentor',
        'ditetapkan_oleh',
        'tanggal_penempatan',
        'tanggal_mulai',
        'tanggal_selesai',
        'status_magang',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_penempatan' => 'date',
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date'
        ];
    }

    public function keputusan(): BelongsTo
    {
        return $this->belongsTo(KeputusanPenerimaan::class, 'id_keputusan', 'id_keputusan');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function seksi(): BelongsTo
    {
        return $this->belongsTo(Seksi::class, 'id_seksi', 'id_seksi');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_mentor', 'id_pegawai');
    }

    public function ditetapkanOleh(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'ditetapkan_oleh', 'id_pegawai');
    }

    public function tugas(): HasMany
    {
        return $this->hasMany(Tugas::class, 'id_peserta_magang', 'id_peserta_magang');
    }

    public function pengajuanKehadiran(): HasMany
    {
        return $this->hasMany(PengajuanKehadiran::class, 'id_peserta_magang', 'id_peserta_magang');
    }

    public function presensi(): HasMany
    {
        return $this->hasMany(Presensi::class, 'id_peserta_magang', 'id_peserta_magang');
    }

    public function logbook(): HasMany
    {
        return $this->hasMany(Logbook::class, 'id_peserta_magang', 'id_peserta_magang');
    }

    public function evaluasiPeserta(): HasOne
    {
        return $this->hasOne(EvaluasiPeserta::class, 'id_peserta_magang', 'id_peserta_magang');
    }

    public function penyelesaianMagang(): HasMany
    {
        return $this->hasMany(PenyelesaianMagang::class, 'id_peserta_magang', 'id_peserta_magang');
    }

    /** Data diri peserta diambil dari calon_peserta lewat keputusan penerimaan. */
    public function calonPeserta(): ?CalonPeserta
    {
        return $this->keputusan?->calon;
    }

    public function scopeAktif($query)
    {
        return $query->where('status_magang', 'Aktif');
    }

    /** Pemeriksa tugas, logbook, izin/susulan, dan evaluasi = mentor peserta ini. */
    public function dibimbingOleh(?Pegawai $pegawai): bool
    {
        return $pegawai !== null && $this->id_mentor === $pegawai->id_pegawai;
    }
}
