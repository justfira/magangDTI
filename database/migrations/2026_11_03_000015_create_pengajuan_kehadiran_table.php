<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_kehadiran', function (Blueprint $table) {
            $table->increments('id_pengajuan_kehadiran');
            $table->unsignedInteger('id_peserta_magang')->comment('mengajukan');
            $table->string('jenis', 20);
            $table->date('tanggal_kegiatan');
            $table->string('jenis_izin', 20)->nullable();
            $table->text('alasan');
            $table->text('keterangan')->nullable();
            $table->string('path_lampiran', 500)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('akurasi_meter', 8, 2)->nullable();
            $table->timestamp('waktu_pengajuan')->useCurrent();
            $table->string('status', 30)->default('Menunggu Verifikasi');
            $table->text('catatan_keputusan')->nullable();
            $table->timestamp('waktu_keputusan')->nullable();
            $table->foreign('id_peserta_magang')->references('id_peserta_magang')->on('peserta_magang')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_kehadiran');
    }
};
