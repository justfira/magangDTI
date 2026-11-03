<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pegawai', function (Blueprint $table) {
            $table->increments('id_pegawai');
            $table->unsignedInteger('id_pengguna')->unique()->comment('adalah');
            $table->unsignedInteger('id_seksi')->nullable()->comment('bertugas di');
            $table->string('nip', 30)->nullable()->unique();
            $table->string('jabatan', 60)->nullable();
            $table->foreign('id_pengguna')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
            $table->foreign('id_seksi')->references('id_seksi')->on('seksi')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai');
    }
};
