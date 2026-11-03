<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `pengajuan_kehadiran` — Pengajuan IZIN atau presensi SUSULAN, diverifikasi mentor peserta (UC-09, UC-11).
 */
class PengajuanKehadiran extends Model
{
    protected $table = 'pengajuan_kehadiran';

    protected $primaryKey = 'id_pengajuan_kehadiran';

    public $timestamps = false;
    public const JENIS = ['IZIN', 'SUSULAN'];
    public const JENIS_IZIN = ['Penuh', 'Sebagian'];
    public const STATUS = ['Menunggu Verifikasi', 'Disetujui', 'Ditolak'];

    protected $fillable = [
        'id_peserta_magang',
        'jenis',
        'tanggal_kegiatan',
        'jenis_izin',
        'alasan',
        'keterangan',
        'path_lampiran',
        'latitude',
        'longitude',
        'akurasi_meter',
        'waktu_pengajuan',
        'status',
        'catatan_keputusan',
        'waktu_keputusan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kegiatan' => 'date',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'akurasi_meter' => 'decimal:2',
            'waktu_pengajuan' => 'datetime',
            'waktu_keputusan' => 'datetime'
        ];
    }

    public function pesertaMagang(): BelongsTo
    {
        return $this->belongsTo(PesertaMagang::class, 'id_peserta_magang', 'id_peserta_magang');
    }

    public function presensi(): HasOne
    {
        return $this->hasOne(Presensi::class, 'id_pengajuan_kehadiran', 'id_pengajuan_kehadiran');
    }
}
