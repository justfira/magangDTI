<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_pengajuan', function (Blueprint $table) {
            $table->increments('id_dokumen');
            $table->unsignedInteger('id_pengajuan')->comment('melampirkan');
            $table->unsignedInteger('id_syarat')->nullable()->comment('memenuhi');
            $table->string('jenis_dokumen', 80);
            $table->string('nama_berkas', 255);
            $table->string('path_berkas', 500);
            $table->unsignedBigInteger('ukuran_bytes')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->string('hasil_pemeriksaan', 20)->nullable();
            $table->text('catatan_pemeriksaan')->nullable();
            $table->foreign('id_pengajuan')->references('id_pengajuan')->on('pengajuan')->restrictOnDelete();
            $table->foreign('id_syarat')->references('id_syarat')->on('syarat_dokumen_program')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_pengajuan');
    }
};
