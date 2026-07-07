<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bk_pelanggaran_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_siswa_id')->constrained('data_siswa')->cascadeOnDelete();
            $table->foreignId('data_kelas_id')->constrained('data_kelas')->cascadeOnDelete();
            $table->foreignId('data_tahun_pelajaran_id')->constrained('data_tahun_pelajaran')->cascadeOnDelete();
            $table->foreignId('bk_jenis_pelanggaran_id')->constrained('bk_jenis_pelanggaran')->restrictOnDelete();
            $table->date('tanggal');
            $table->unsignedInteger('poin')->default(0);
            $table->string('status', 40)->default('Baru');
            $table->text('kronologi')->nullable();
            $table->text('tindakan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamps();

            $table->index('data_siswa_id');
            $table->index('data_kelas_id');
            $table->index('data_tahun_pelajaran_id');
            $table->index('bk_jenis_pelanggaran_id');
            $table->index('tanggal');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bk_pelanggaran_siswa');
    }
};

