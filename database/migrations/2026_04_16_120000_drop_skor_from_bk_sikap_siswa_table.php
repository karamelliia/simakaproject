<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bk_sikap_siswa', function (Blueprint $table) {
            if (Schema::hasColumn('bk_sikap_siswa', 'skor')) {
                $table->dropColumn('skor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bk_sikap_siswa', function (Blueprint $table) {
            if (!Schema::hasColumn('bk_sikap_siswa', 'skor')) {
                $table->unsignedTinyInteger('skor')->nullable()->after('predikat');
            }
        });
    }
};
