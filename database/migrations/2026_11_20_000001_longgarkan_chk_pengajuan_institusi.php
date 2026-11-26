<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * UC-04: permohonan institusi kini juga bisa masuk lewat form online publik (tanpa login),
 * sehingga `dicatat_oleh` boleh NULL. Aturan lama mewajibkan `dicatat_oleh` terisi dan
 * membuat setiap kiriman form online gagal ("Gagal menyimpan permohonan, silakan coba lagi.").
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            return;
        }
        DB::statement('ALTER TABLE `pengajuan` DROP CONSTRAINT `chk_pengajuan_institusi`');
        DB::statement(<<<'SQL'
ALTER TABLE `pengajuan`
  ADD CONSTRAINT `chk_pengajuan_institusi` CHECK (`jalur` <> 'INSTITUSI' OR (`nama_institusi` IS NOT NULL AND `nomor_surat` IS NOT NULL))
SQL);
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            return;
        }
        DB::statement('ALTER TABLE `pengajuan` DROP CONSTRAINT `chk_pengajuan_institusi`');
        DB::statement(<<<'SQL'
ALTER TABLE `pengajuan`
  ADD CONSTRAINT `chk_pengajuan_institusi` CHECK (`jalur` <> 'INSTITUSI' OR (`dicatat_oleh` IS NOT NULL AND `nama_institusi` IS NOT NULL AND `nomor_surat` IS NOT NULL))
SQL);
    }
};
