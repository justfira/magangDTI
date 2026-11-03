<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi', function (Blueprint $table) {
            $table->increments('id_presensi');
            $table->unsignedInteger('id_peserta_magang')->comment('melakukan');
            $table->unsignedInteger('id_pengajuan_kehadiran')->nullable()->unique()->comment('menghasilkan');
            $table->date('tanggal');
            $table->string('sumber', 20)->default('REGULER');
            $table->timestamp('waktu_checkin')->nullable();
            $table->decimal('lat_checkin', 10, 7)->nullable();
            $table->decimal('long_checkin', 10, 7)->nullable();
            $table->decimal('akurasi_checkin', 8, 2)->nullable();
            $table->timestamp('waktu_checkout')->nullable();
            $table->decimal('lat_checkout', 10, 7)->nullable();
            $table->decimal('long_checkout', 10, 7)->nullable();
            $table->decimal('akurasi_checkout', 8, 2)->nullable();
            $table->unique(['id_peserta_magang', 'tanggal']);
            $table->foreign('id_peserta_magang')->references('id_peserta_magang')->on('peserta_magang')->restrictOnDelete();
            $table->foreign('id_pengajuan_kehadiran')->references('id_pengajuan_kehadiran')->on('pengajuan_kehadiran')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi');
    }
};
