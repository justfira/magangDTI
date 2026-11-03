<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syarat_dokumen_program', function (Blueprint $table) {
            $table->increments('id_syarat');
            $table->unsignedInteger('id_program')->comment('mensyaratkan');
            $table->string('nama_syarat', 150);
            $table->boolean('wajib')->default(true);
            $table->integer('urutan')->default(1);
            $table->unique(['id_program', 'urutan']);
            $table->foreign('id_program')->references('id_program')->on('program_magang')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syarat_dokumen_program');
    }
};
