<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabel `pengguna` — Akun login semua orang (pegawai DTI maupun calon/peserta).
 */
class Pengguna extends Authenticatable
{
    protected $table = 'pengguna';

    protected $primaryKey = 'id_pengguna';


    protected $fillable = [
        'id_peran',
        'nama_lengkap',
        'email',
        'password_hash',
        'status_akun',
    ];

    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return [
            
        ];
    }

    public function peran(): BelongsTo
    {
        return $this->belongsTo(Peran::class, 'id_peran', 'id_peran');
    }

    public function pegawai(): HasOne
    {
        return $this->hasOne(Pegawai::class, 'id_pengguna', 'id_pengguna');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'id_pengguna', 'id_pengguna');
    }

    public function pengajuanSaya(): HasMany
    {
        return $this->hasMany(Pengajuan::class, 'diajukan_oleh', 'id_pengguna');
    }

    public function pesertaMagang(): HasMany
    {
        return $this->hasMany(PesertaMagang::class, 'id_pengguna', 'id_pengguna');
    }

    /** Kolom password di tabel ini bernama password_hash. */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /** Tabel pengguna tidak punya remember_token (sesuai ERD). */
    protected $rememberTokenName = '';

    public function kodePeran(): ?string
    {
        return $this->peran?->kode_peran;
    }

    public function punyaPeran(string ...$kode): bool
    {
        return in_array($this->kodePeran(), $kode, true);
    }

    public function isAktif(): bool
    {
        return $this->status_akun === 'Aktif';
    }

    /** Peserta magang yang sedang berjalan (status Belum Mulai / Aktif). */
    public function pesertaMagangBerjalan(): ?PesertaMagang
    {
        return $this->pesertaMagang()->whereIn('status_magang', ['Belum Mulai', 'Aktif'])->latest('id_peserta_magang')->first();
    }
}
