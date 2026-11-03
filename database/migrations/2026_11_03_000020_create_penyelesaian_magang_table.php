<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penyelesaian_magang', function (Blueprint $table) {
            $table->increments('id_penyelesaian');
            $table->unsignedInteger('id_peserta_magang')->comment('diselesaikan');
            $table->unsignedInteger('id_kepala_seksi')->comment('merekomendasikan');
            $table->unsignedInteger('id_direktur')->nullable()->comment('memutuskan');
            $table->unsignedInteger('id_pengunggah')->nullable()->comment('mengunggah');
            $table->string('rekomendasi', 40);
            $table->text('catatan_rekomendasi')->nullable();
            $table->timestamp('waktu_rekomendasi')->useCurrent();
            $table->string('keputusan', 25)->nullable();
            $table->text('catatan_keputusan')->nullable();
            $table->timestamp('waktu_keputusan')->nullable();
            $table->string('nomor_sertifikat', 100)->nullable()->unique();
            $table->string('path_sertifikat', 500)->nullable();
            $table->timestamp('waktu_unggah')->nullable();
            $table->foreign('id_peserta_magang')->references('id_peserta_magang')->on('peserta_magang')->restrictOnDelete();
            $table->foreign('id_kepala_seksi')->references('id_pegawai')->on('pegawai')->restrictOnDelete();
            $table->foreign('id_direktur')->references('id_pegawai')->on('pegawai')->restrictOnDelete();
            $table->foreign('id_pengunggah')->references('id_pegawai')->on('pegawai')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penyelesaian_magang');
    }
};
