<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_jam_siswa', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('data_tahun_pelajaran_id')->constrained('data_tahun_pelajaran');
            $table->enum('semester', ['Ganjil', 'Genap']);
            $table->foreignId('data_kelas_id')->constrained('data_kelas');
            $table->foreignId('data_mapel_id')->constrained('data_mapel');
            $table->foreignId('guru_id')->constrained('pengguna');
            $table->foreignId('data_siswa_id')->constrained('data_siswa');
            $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']);
            $table->unsignedTinyInteger('jam_ke');
            $table->enum('status', ['H', 'S', 'I', 'A'])->default('H');
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->unique(['tanggal', 'data_siswa_id', 'jam_ke'], 'uniq_absensi_siswa_tanggal_jam');
            $table->index(['data_kelas_id', 'tanggal']);
            $table->index(['data_tahun_pelajaran_id', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_jam_siswa');
    }
};

