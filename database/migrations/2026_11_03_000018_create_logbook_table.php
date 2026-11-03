<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logbook', function (Blueprint $table) {
            $table->increments('id_logbook');
            $table->unsignedInteger('id_peserta_magang')->comment('mengisi');
            $table->date('tanggal_kegiatan');
            $table->text('uraian_aktivitas');
            $table->string('status', 30)->default('Dikirim');
            $table->text('catatan_mentor')->nullable();
            $table->timestamp('waktu_kirim')->nullable();
            $table->timestamp('waktu_periksa')->nullable();
            $table->unique(['id_peserta_magang', 'tanggal_kegiatan']);
            $table->foreign('id_peserta_magang')->references('id_peserta_magang')->on('peserta_magang')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logbook');
    }
};
