<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan', function (Blueprint $table) {
            $table->increments('id_pengajuan');
            $table->unsignedInteger('id_program')->nullable()->comment('menerima');
            $table->unsignedInteger('diajukan_oleh')->nullable()->comment('mendaftar');
            $table->unsignedInteger('dicatat_oleh')->nullable()->comment('mencatat');
            $table->unsignedInteger('id_pemeriksa')->nullable()->comment('memeriksa');
            $table->string('kode_pengajuan', 40)->unique();
            $table->string('jalur', 20);
            $table->string('nama_institusi', 180)->nullable();
            $table->string('nomor_surat', 100)->nullable();
            $table->date('tanggal_surat')->nullable();
            $table->string('kontak_institusi', 150)->nullable();
            $table->string('status_pengajuan', 35)->default('Diajukan');
            $table->text('catatan_perbaikan')->nullable();
            $table->timestamp('waktu_pemeriksaan')->nullable();
            $table->timestamp('tanggal_pengajuan')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['diajukan_oleh', 'id_program']);
            $table->foreign('id_program')->references('id_program')->on('program_magang')->restrictOnDelete();
            $table->foreign('diajukan_oleh')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
            $table->foreign('dicatat_oleh')->references('id_pegawai')->on('pegawai')->restrictOnDelete();
            $table->foreign('id_pemeriksa')->references('id_pegawai')->on('pegawai')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan');
    }
};
