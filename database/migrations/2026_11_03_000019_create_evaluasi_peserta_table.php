<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluasi_peserta', function (Blueprint $table) {
            $table->increments('id_evaluasi');
            $table->unsignedInteger('id_peserta_magang')->unique()->comment('dievaluasi');
            $table->string('status_evaluasi', 30);
            $table->text('catatan')->nullable();
            $table->string('path_dokumen_evaluasi', 500)->nullable();
            $table->timestamp('waktu_evaluasi')->nullable();
            $table->foreign('id_peserta_magang')->references('id_peserta_magang')->on('peserta_magang')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasi_peserta');
    }
};
