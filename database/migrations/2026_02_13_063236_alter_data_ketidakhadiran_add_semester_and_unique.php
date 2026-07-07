<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) tambah kolom semester kalau belum ada
        Schema::table('data_ketidakhadiran', function (Blueprint $table) {
            if (!Schema::hasColumn('data_ketidakhadiran', 'semester')) {
                $table->enum('semester', ['Ganjil', 'Genap'])
                    ->default('Ganjil')
                    ->after('data_tahun_pelajaran_id');
            }
        });

        // 2) drop foreign key dulu (kalau ada)
        $this->dropForeignIfExists('data_ketidakhadiran', 'data_ketidakhadiran_data_siswa_id_foreign');
        $this->dropForeignIfExists('data_ketidakhadiran', 'data_ketidakhadiran_data_tahun_pelajaran_id_foreign');

        // 3) drop unique lama (kalau ada)
        $this->dropIndexIfExists('data_ketidakhadiran', 'data_ketidakhadiran_data_siswa_id_data_tahun_pelajaran_id_unique');
        $this->dropIndexIfExists('data_ketidakhadiran', 'dk_unique_siswa_tahun_semester');

        // 4) buat unique baru (siswa+tahun+semester)
        DB::statement("
            ALTER TABLE data_ketidakhadiran
            ADD UNIQUE KEY dk_unique_siswa_tahun_semester (data_siswa_id, data_tahun_pelajaran_id, semester)
        ");

        // 5) tambahkan foreign key lagi (pastikan nama tabel referensi benar)
        $this->addForeignIfNotExists(
            'data_ketidakhadiran',
            'data_ketidakhadiran_data_siswa_id_foreign',
            'data_siswa_id',
            'data_siswa',
            'id',
            'CASCADE'
        );

        $this->addForeignIfNotExists(
            'data_ketidakhadiran',
            'data_ketidakhadiran_data_tahun_pelajaran_id_foreign',
            'data_tahun_pelajaran_id',
            'data_tahun_pelajaran',
            'id',
            'CASCADE'
        );
    }

    public function down(): void
    {
        // drop FK dulu
        $this->dropForeignIfExists('data_ketidakhadiran', 'data_ketidakhadiran_data_siswa_id_foreign');
        $this->dropForeignIfExists('data_ketidakhadiran', 'data_ketidakhadiran_data_tahun_pelajaran_id_foreign');

        // drop unique baru
        $this->dropIndexIfExists('data_ketidakhadiran', 'dk_unique_siswa_tahun_semester');

        // hapus kolom semester
        Schema::table('data_ketidakhadiran', function (Blueprint $table) {
            if (Schema::hasColumn('data_ketidakhadiran', 'semester')) {
                $table->dropColumn('semester');
            }
        });

        // balikin unique lama (opsional)
        DB::statement("
            ALTER TABLE data_ketidakhadiran
            ADD UNIQUE KEY data_ketidakhadiran_data_siswa_id_data_tahun_pelajaran_id_unique
            (data_siswa_id, data_tahun_pelajaran_id)
        ");

        // tambahkan FK lagi
        $this->addForeignIfNotExists(
            'data_ketidakhadiran',
            'data_ketidakhadiran_data_siswa_id_foreign',
            'data_siswa_id',
            'data_siswa',
            'id',
            'CASCADE'
        );

        $this->addForeignIfNotExists(
            'data_ketidakhadiran',
            'data_ketidakhadiran_data_tahun_pelajaran_id_foreign',
            'data_tahun_pelajaran_id',
            'data_tahun_pelajaran',
            'id',
            'CASCADE'
        );
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $exists = DB::selectOne("
            SELECT COUNT(1) AS cnt
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND index_name = ?
        ", [$table, $indexName]);

        if ($exists && (int) $exists->cnt > 0) {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$indexName}");
        }
    }

    private function dropForeignIfExists(string $table, string $fkName): void
    {
        $exists = DB::selectOne("
            SELECT COUNT(1) AS cnt
            FROM information_schema.table_constraints
            WHERE constraint_schema = DATABASE()
              AND table_name = ?
              AND constraint_name = ?
              AND constraint_type = 'FOREIGN KEY'
        ", [$table, $fkName]);

        if ($exists && (int) $exists->cnt > 0) {
            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$fkName}");
        }
    }

    private function addForeignIfNotExists(
        string $table,
        string $fkName,
        string $column,
        string $refTable,
        string $refColumn,
        string $onDelete = 'RESTRICT'
    ): void {
        $exists = DB::selectOne("
            SELECT COUNT(1) AS cnt
            FROM information_schema.table_constraints
            WHERE constraint_schema = DATABASE()
              AND table_name = ?
              AND constraint_name = ?
              AND constraint_type = 'FOREIGN KEY'
        ", [$table, $fkName]);

        if (!$exists || (int) $exists->cnt === 0) {
            DB::statement("
                ALTER TABLE {$table}
                ADD CONSTRAINT {$fkName}
                FOREIGN KEY ({$column})
                REFERENCES {$refTable}({$refColumn})
                ON DELETE {$onDelete}
            ");
        }
    }
};
