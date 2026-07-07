<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bk_jenis_pelanggaran', function (Blueprint $table) {
            $table->boolean('is_terminal')->default(false)->after('poin_default');
            $table->boolean('affects_pkl_score')->default(true)->after('is_terminal');
        });

        $terminalNames = [
            'Terlibat pemerkosaan atau hamil di luar nikah',
            'Perbuatan asusila (berzina, free sex, homo & lesbi, sodomi, pemerkosaan, aborsi, pelecehan seksual, pacaran diluar batas)',
            'Membawa, melihat, membuat atau mengedarkan konten pornografi',
        ];

        DB::table('bk_jenis_pelanggaran')
            ->whereIn('nama_pelanggaran', $terminalNames)
            ->update([
                'kategori' => 'sangat_berat',
                'poin_default' => 10000,
                'is_terminal' => true,
                'penanganan' => 'BK, Waka Kesis, Kepala Sekolah, Pemanggilan Orang Tua, Proses Keputusan Resmi',
                'updated_at' => now(),
            ]);

        $terminalIds = DB::table('bk_jenis_pelanggaran')
            ->where('is_terminal', true)
            ->pluck('id');

        if ($terminalIds->isNotEmpty()) {
            DB::table('bk_pelanggaran_siswa')
                ->whereIn('bk_jenis_pelanggaran_id', $terminalIds)
                ->update(['poin' => 10000]);
        }

        DB::table('bk_jenis_pelanggaran')
            ->whereIn('nama_pelanggaran', [
                'Alfa 3x berturut turut',
                'Tidak hadir tanpa keterangan',
            ])
            ->update([
                'affects_pkl_score' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('bk_jenis_pelanggaran', function (Blueprint $table) {
            $table->dropColumn(['is_terminal', 'affects_pkl_score']);
        });
    }
};
