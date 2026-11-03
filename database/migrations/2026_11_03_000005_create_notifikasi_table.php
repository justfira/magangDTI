<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->increments('id_notifikasi');
            $table->unsignedInteger('id_pengguna')->comment('menerima');
            $table->string('jenis_entitas', 80)->nullable();
            $table->integer('id_entitas')->nullable();
            $table->string('jenis', 80);
            $table->text('pesan');
            $table->boolean('sudah_dibaca')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->index(['id_pengguna', 'sudah_dibaca']);
            $table->foreign('id_pengguna')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
