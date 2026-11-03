<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seksi', function (Blueprint $table) {
            $table->increments('id_seksi');
            $table->string('nama_seksi', 120)->unique();
            $table->text('deskripsi')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seksi');
    }
};
