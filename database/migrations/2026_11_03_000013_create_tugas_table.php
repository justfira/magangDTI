<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tugas', function (Blueprint $table) {
            $table->increments('id_tugas');
            $table->unsignedInteger('id_peserta_magang')->comment('menerima');
            $table->string('judul', 180);
            $table->text('deskripsi')->nullable();
            $table->date('tenggat');
            $table->unsignedTinyInteger('jumlah_progres')->default(1)->comment('Jumlah target progres yang ditentukan mentor saat membuat tugas');
            $table->json('judul_progres')->nullable()->comment('Daftar judul untuk tiap tahapan progres dalam format JSON');
            $table->string('status', 30)->default('Ditugaskan');
            $table->unsignedSmallInteger('nilai')->nullable()->comment('Nilai akhir tugas (0-100)');
            $table->text('catatan_nilai')->nullable()->comment('Catatan atau umpan balik penilaian akhir');
            $table->timestamp('created_at')->nullable();
            $table->foreign('id_peserta_magang')->references('id_peserta_magang')->on('peserta_magang')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tugas');
    }
};
