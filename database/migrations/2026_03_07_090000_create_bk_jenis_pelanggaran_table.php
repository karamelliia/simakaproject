<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bk_jenis_pelanggaran', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama_pelanggaran', 150);
            $table->unsignedInteger('poin_default')->default(0);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();

            $table->index('status_aktif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bk_jenis_pelanggaran');
    }
};

