<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('koreksi_presensi', function (Blueprint $table) {
            $table->increments('id_koreksi');
            $table->unsignedInteger('id_presensi')->comment('dikoreksi');
            $table->unsignedInteger('id_pengoreksi')->comment('mengoreksi');
            $table->text('alasan');
            $table->string('path_bukti', 500);
            $table->json('data_sebelum')->nullable();
            $table->json('data_sesudah');
            $table->timestamp('waktu_koreksi')->useCurrent();
            $table->foreign('id_presensi')->references('id_presensi')->on('presensi')->restrictOnDelete();
            $table->foreign('id_pengoreksi')->references('id_pegawai')->on('pegawai')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('koreksi_presensi');
    }
};
