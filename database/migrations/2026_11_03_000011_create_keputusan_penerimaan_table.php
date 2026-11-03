<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keputusan_penerimaan', function (Blueprint $table) {
            $table->increments('id_keputusan');
            $table->unsignedInteger('id_calon')->unique()->comment('diputuskan');
            $table->unsignedInteger('id_kepala_seksi')->comment('merekomendasikan');
            $table->unsignedInteger('id_direktur')->nullable()->comment('memutuskan');
            $table->string('rekomendasi', 20);
            $table->text('catatan_rekomendasi')->nullable();
            $table->timestamp('waktu_rekomendasi')->useCurrent();
            $table->string('keputusan', 20)->nullable();
            $table->text('catatan_keputusan')->nullable();
            $table->timestamp('waktu_keputusan')->nullable();
            $table->foreign('id_calon')->references('id_calon')->on('calon_peserta')->restrictOnDelete();
            $table->foreign('id_kepala_seksi')->references('id_pegawai')->on('pegawai')->restrictOnDelete();
            $table->foreign('id_direktur')->references('id_pegawai')->on('pegawai')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keputusan_penerimaan');
    }
};
