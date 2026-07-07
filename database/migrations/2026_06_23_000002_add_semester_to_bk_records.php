<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bk_sikap_siswa', function (Blueprint $table) {
            $table->string('semester', 10)->nullable()->after('data_tahun_pelajaran_id');
            $table->index(['data_tahun_pelajaran_id', 'semester'], 'bk_sikap_tahun_semester_idx');
        });

        Schema::table('bk_pelanggaran_siswa', function (Blueprint $table) {
            $table->string('semester', 10)->nullable()->after('data_tahun_pelajaran_id');
            $table->index(['data_tahun_pelajaran_id', 'semester'], 'bk_pelanggaran_tahun_semester_idx');
        });

        DB::table('bk_sikap_siswa')
            ->select('id', 'tanggal_penilaian')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('bk_sikap_siswa')->where('id', $row->id)->update([
                        'semester' => Carbon::parse($row->tanggal_penilaian)->month >= 7 ? 'Ganjil' : 'Genap',
                    ]);
                }
            });

        DB::table('bk_pelanggaran_siswa')
            ->select('id', 'tanggal')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('bk_pelanggaran_siswa')->where('id', $row->id)->update([
                        'semester' => Carbon::parse($row->tanggal)->month >= 7 ? 'Ganjil' : 'Genap',
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('bk_sikap_siswa', function (Blueprint $table) {
            $table->dropIndex('bk_sikap_tahun_semester_idx');
            $table->dropColumn('semester');
        });

        Schema::table('bk_pelanggaran_siswa', function (Blueprint $table) {
            $table->dropIndex('bk_pelanggaran_tahun_semester_idx');
            $table->dropColumn('semester');
        });
    }
};
