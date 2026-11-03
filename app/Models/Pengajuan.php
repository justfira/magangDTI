<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `pengajuan` — Pengajuan magang jalur DTI (perorangan) atau INSTITUSI (surat). Kesimpulan cek kelengkapan disimpan di sini (UC-03, UC-04, UC-05).
 */
class Pengajuan extends Model
{
    protected $table = 'pengajuan';

    protected $primaryKey = 'id_pengajuan';

    public $timestamps = false;
    public const JALUR = ['DTI', 'INSTITUSI'];
    public const STATUS = ['Diajukan', 'Perlu Perbaikan', 'Lengkap', 'Selesai Diputuskan'];

    protected $fillable = [
        'id_program',
        'diajukan_oleh',
        'dicatat_oleh',
        'id_pemeriksa',
        'kode_pengajuan',
        'jalur',
        'nama_institusi',
        'nomor_surat',
        'tanggal_surat',
        'kontak_institusi',
        'status_pengajuan',
        'catatan_perbaikan',
        'waktu_pemeriksaan',
        'tanggal_pengajuan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'waktu_pemeriksaan' => 'datetime',
            'tanggal_pengajuan' => 'datetime'
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ProgramMagang::class, 'id_program', 'id_program');
    }

    public function diajukanOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diajukan_oleh', 'id_pengguna');
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'dicatat_oleh', 'id_pegawai');
    }

    public function pemeriksa(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_pemeriksa', 'id_pegawai');
    }

    public function dokumenPengajuan(): HasMany
    {
        return $this->hasMany(DokumenPengajuan::class, 'id_pengajuan', 'id_pengajuan');
    }

    public function calonPeserta(): HasMany
    {
        return $this->hasMany(CalonPeserta::class, 'id_pengajuan', 'id_pengajuan');
    }
}
