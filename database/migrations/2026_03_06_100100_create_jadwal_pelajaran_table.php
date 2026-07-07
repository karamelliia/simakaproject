<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_pelajaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_tahun_pelajaran_id')->constrained('data_tahun_pelajaran');
            $table->foreignId('data_kelas_id')->constrained('data_kelas');
            $table->foreignId('data_mapel_id')->constrained('data_mapel');
            $table->foreignId('guru_id')->constrained('pengguna');
            $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']);
            $table->unsignedTinyInteger('jam_ke');
            $table->timestamps();

            $table->index(['guru_id', 'hari', 'jam_ke']);
            $table->unique(['data_tahun_pelajaran_id', 'data_kelas_id', 'hari', 'jam_ke'], 'uniq_jadwal_slot_kelas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_pelajaran');
    }
};

