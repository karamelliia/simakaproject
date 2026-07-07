<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ranges = [
            1 => ['07:00:00', '08:10:00'],
            2 => ['07:00:00', '08:10:00'],
            3 => ['08:10:00', '09:20:00'],
            4 => ['08:10:00', '09:20:00'],
            5 => ['09:35:00', '10:40:00'],
            6 => ['09:35:00', '10:40:00'],
            7 => ['10:40:00', '11:40:00'],
            8 => ['10:40:00', '11:40:00'],
            9 => ['13:00:00', '14:10:00'],
            10 => ['13:00:00', '14:10:00'],
        ];

        foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari) {
            foreach ($ranges as $jamKe => [$mulai, $selesai]) {
                DB::table('jam_pelajaran')
                    ->where('hari', $hari)
                    ->where('jam_ke', $jamKe)
                    ->update([
                        'jam_mulai' => $mulai,
                        'jam_selesai' => $selesai,
                        'aktif' => true,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('jam_pelajaran')->update([
            'jam_mulai' => null,
            'jam_selesai' => null,
            'updated_at' => now(),
        ]);
    }
};
