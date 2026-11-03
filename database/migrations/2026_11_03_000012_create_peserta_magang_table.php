<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peserta_magang', function (Blueprint $table) {
            $table->increments('id_peserta_magang');
            $table->unsignedInteger('id_keputusan')->unique()->comment('menjadi');
            $table->unsignedInteger('id_pengguna')->comment('memakai akun');
            $table->unsignedInteger('id_seksi')->nullable()->comment('ditempatkan');
            $table->unsignedInteger('id_mentor')->nullable()->comment('membimbing');
            $table->unsignedInteger('ditetapkan_oleh')->nullable()->comment('menetapkan');
            $table->date('tanggal_penempatan')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('status_magang', 30)->default('Belum Mulai');
            $table->timestamp('created_at')->nullable();
            $table->foreign('id_keputusan')->references('id_keputusan')->on('keputusan_penerimaan')->restrictOnDelete();
            $table->foreign('id_pengguna')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
            $table->foreign('id_seksi')->references('id_seksi')->on('seksi')->restrictOnDelete();
            $table->foreign('id_mentor')->references('id_pegawai')->on('pegawai')->restrictOnDelete();
            $table->foreign('ditetapkan_oleh')->references('id_pegawai')->on('pegawai')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peserta_magang');
    }
};
