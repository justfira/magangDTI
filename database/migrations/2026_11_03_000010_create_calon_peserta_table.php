<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calon_peserta', function (Blueprint $table) {
            $table->increments('id_calon');
            $table->unsignedInteger('id_pengajuan')->comment('memuat');
            $table->string('nama_lengkap', 150);
            $table->string('email', 191)->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->string('nomor_induk_pendidikan', 40)->nullable();
            $table->string('institusi_asal', 180)->nullable();
            $table->string('jurusan_prodi', 150)->nullable();
            $table->foreign('id_pengajuan')->references('id_pengajuan')->on('pengajuan')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calon_peserta');
    }
};
