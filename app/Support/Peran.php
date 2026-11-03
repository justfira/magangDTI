<?php

namespace App\Support;

/** Kode peran sesuai tabel `peran` (A2 bagian 2.1). */
final class Peran
{
    public const SUPERADMIN = 'SUPERADMIN';
    public const MENTOR = 'MENTOR';
    public const KEPALA_SEKSI = 'KEPALA_SEKSI';
    public const DIREKTUR = 'DIREKTUR';
    public const PESERTA = 'PESERTA';   // calon peserta & peserta magang

    public const PEGAWAI = [self::SUPERADMIN, self::MENTOR, self::KEPALA_SEKSI, self::DIREKTUR];
}
