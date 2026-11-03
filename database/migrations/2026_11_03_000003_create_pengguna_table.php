<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengguna', function (Blueprint $table) {
            $table->increments('id_pengguna');
            $table->unsignedInteger('id_peran')->comment('memiliki');
            $table->string('nama_lengkap', 150);
            $table->string('email', 191)->unique();
            $table->string('password_hash', 255);
            $table->string('status_akun', 25)->default('Aktif');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->foreign('id_peran')->references('id_peran')->on('peran')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengguna');
    }
};
