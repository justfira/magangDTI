<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_magang', function (Blueprint $table) {
            $table->increments('id_program');
            $table->unsignedInteger('dibuat_oleh')->comment('membuat');
            $table->string('nama_program', 150);
            $table->date('tanggal_buka');
            $table->date('tanggal_tutup');
            $table->text('informasi')->nullable();
            $table->text('persyaratan')->nullable();
            $table->string('status_publikasi', 30)->default('Draft');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->foreign('dibuat_oleh')->references('id_pegawai')->on('pegawai')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_magang');
    }
};
