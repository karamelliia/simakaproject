<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hubin_rekomendasi_pkl_settings', function (Blueprint $table) {
            $table->id();
            $table->json('weights')->nullable();
            $table->json('grade_thresholds')->nullable();
            $table->unsignedTinyInteger('attendance_default_score_without_data')->default(70);
            $table->foreignId('updated_by')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hubin_rekomendasi_pkl_settings');
    }
};
