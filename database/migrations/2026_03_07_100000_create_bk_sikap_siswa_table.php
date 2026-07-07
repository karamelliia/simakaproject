<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bk_sikap_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_siswa_id')->constrained('data_siswa')->cascadeOnDelete();
            $table->foreignId('data_kelas_id')->constrained('data_kelas')->cascadeOnDelete();
            $table->foreignId('data_tahun_pelajaran_id')->constrained('data_tahun_pelajaran')->cascadeOnDelete();
            $table->date('tanggal_penilaian');
            $table->string('aspek_sikap', 120);
            $table->string('predikat', 20)->default('Baik');
            $table->unsignedTinyInteger('skor')->nullable();
            $table->string('status', 30)->default('Perlu Monitoring');
            $table->text('catatan')->nullable();
            $table->text('tindak_lanjut')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamps();

            $table->index('data_siswa_id');
            $table->index('data_kelas_id');
            $table->index('data_tahun_pelajaran_id');
            $table->index('tanggal_penilaian');
            $table->index('predikat');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bk_sikap_siswa');
    }
};

