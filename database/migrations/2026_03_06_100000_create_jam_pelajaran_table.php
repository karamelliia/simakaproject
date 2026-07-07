<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jam_pelajaran', function (Blueprint $table) {
            $table->id();
            $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']);
            $table->unsignedTinyInteger('jam_ke');
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['hari', 'jam_ke']);
        });

        $now = now()->toDateTimeString();
        $rows = [];
        foreach (['Senin', 'Selasa', 'Rabu', 'Kamis'] as $hari) {
            for ($jam = 1; $jam <= 10; $jam++) {
                $rows[] = [
                    'hari' => $hari,
                    'jam_ke' => $jam,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        for ($jam = 1; $jam <= 8; $jam++) {
            $rows[] = [
                'hari' => 'Jumat',
                'jam_ke' => $jam,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('jam_pelajaran')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('jam_pelajaran');
    }
};

