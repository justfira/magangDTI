<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progres_tugas', function (Blueprint $table) {
            $table->increments('id_progres');
            $table->unsignedInteger('id_tugas')->comment('dilaporkan');
            $table->integer('urutan');
            $table->text('catatan_progres');
            $table->string('tautan_hasil', 500)->nullable();
            $table->string('path_berkas', 500)->nullable();
            $table->timestamp('waktu_kirim')->useCurrent();
            $table->string('hasil_review', 30)->nullable();
            $table->text('catatan_review')->nullable();
            $table->timestamp('waktu_review')->nullable();
            $table->unique(['id_tugas', 'urutan']);
            $table->foreign('id_tugas')->references('id_tugas')->on('tugas')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progres_tugas');
    }
};
