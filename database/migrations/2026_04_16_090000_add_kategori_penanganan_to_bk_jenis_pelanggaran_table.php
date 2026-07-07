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
            $table->string('kategori', 20)->default('ringan')->after('nama_pelanggaran');
            $table->string('penanganan', 255)->nullable()->after('poin_default');
            $table->unsignedInteger('urutan')->default(0)->after('penanganan');

            $table->index('kategori');
            $table->index('urutan');
        });

        if (!DB::table('bk_jenis_pelanggaran')->where('id', '>', 0)->exists()) {
            $now = now();

            DB::table('bk_jenis_pelanggaran')->insert([
                ['kode' => 'R01', 'nama_pelanggaran' => 'Atribut sekolah tidak lengkap', 'kategori' => 'ringan', 'poin_default' => 10, 'penanganan' => 'BK', 'urutan' => 1, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'R02', 'nama_pelanggaran' => 'Rok sempit, ketat dan merubah model', 'kategori' => 'ringan', 'poin_default' => 20, 'penanganan' => 'BK', 'urutan' => 2, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'R03', 'nama_pelanggaran' => 'Memakai perhiasan/asesoris kecuali jam tangan dan anting', 'kategori' => 'ringan', 'poin_default' => 20, 'penanganan' => 'Tim Disiplin', 'urutan' => 3, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'R04', 'nama_pelanggaran' => 'Berkuku panjang dan diwarnai', 'kategori' => 'ringan', 'poin_default' => 25, 'penanganan' => 'BK', 'urutan' => 4, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'R05', 'nama_pelanggaran' => 'Celana laki laki tidak sesuai dengan ukuran yang ditentukan oleh pihak sekolah', 'kategori' => 'ringan', 'poin_default' => 25, 'penanganan' => 'BK', 'urutan' => 5, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'R06', 'nama_pelanggaran' => 'Cabut saat PMB', 'kategori' => 'ringan', 'poin_default' => 25, 'penanganan' => 'GMP, BK', 'urutan' => 6, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'R07', 'nama_pelanggaran' => 'Memakai make up', 'kategori' => 'ringan', 'poin_default' => 25, 'penanganan' => 'Guru, BK', 'urutan' => 7, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'R08', 'nama_pelanggaran' => 'Terlambat datang ke sekolah', 'kategori' => 'ringan', 'poin_default' => 30, 'penanganan' => 'Tim Disiplin', 'urutan' => 8, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'R09', 'nama_pelanggaran' => 'Tidak memakai seragam (baju, celana, rok, sepatu, kaos kaki, ikat pinggang) baju dikeluarkan', 'kategori' => 'ringan', 'poin_default' => 30, 'penanganan' => 'BK', 'urutan' => 9, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'R10', 'nama_pelanggaran' => 'Rambut siswa di cat dan tidak sesuai ukuran', 'kategori' => 'ringan', 'poin_default' => 35, 'penanganan' => 'BK', 'urutan' => 10, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'R11', 'nama_pelanggaran' => 'Topi diluar seragam sekolah', 'kategori' => 'ringan', 'poin_default' => 40, 'penanganan' => 'Tim Disiplin', 'urutan' => 11, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'R12', 'nama_pelanggaran' => 'Keluar pekarangan tanpa izin guru piket', 'kategori' => 'ringan', 'poin_default' => 50, 'penanganan' => 'BK', 'urutan' => 12, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'R13', 'nama_pelanggaran' => 'Alfa 3x berturut turut', 'kategori' => 'ringan', 'poin_default' => 50, 'penanganan' => 'BK', 'urutan' => 13, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'R14', 'nama_pelanggaran' => 'Tidak ikut upacara, sholat dzuhur, SKJ', 'kategori' => 'ringan', 'poin_default' => 50, 'penanganan' => 'Guru, BK', 'urutan' => 14, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'S15', 'nama_pelanggaran' => 'Knalpot brong', 'kategori' => 'sedang', 'poin_default' => 75, 'penanganan' => 'Tim Disiplin, Waka Kesis', 'urutan' => 15, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'S16', 'nama_pelanggaran' => 'Menggunakan ruang bengkel tanpa seizin kepala bengkel', 'kategori' => 'sedang', 'poin_default' => 100, 'penanganan' => 'Kepala Bengkel', 'urutan' => 16, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'S17', 'nama_pelanggaran' => 'Berperilaku tidak sopan terhadap sesama siswa dan guru', 'kategori' => 'sedang', 'poin_default' => 100, 'penanganan' => 'Guru, BK', 'urutan' => 17, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'S18', 'nama_pelanggaran' => 'Mencoret dinding, pagar, mobiler, bangunan sekolah', 'kategori' => 'sedang', 'poin_default' => 100, 'penanganan' => 'Guru, BK', 'urutan' => 18, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'S19', 'nama_pelanggaran' => 'Penghinaan, mencaci maki terhadap sesama siswa', 'kategori' => 'sedang', 'poin_default' => 150, 'penanganan' => 'Guru, BK, Waka Kesis', 'urutan' => 19, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'S20', 'nama_pelanggaran' => 'Memalsukan tanda tangan dokumen', 'kategori' => 'sedang', 'poin_default' => 200, 'penanganan' => 'BK, Waka Kesis, Pemanggilan Orang Tua, Surat Perjanjian', 'urutan' => 20, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'S21', 'nama_pelanggaran' => 'Siswa berkelahi sesama siswa atau orang lain', 'kategori' => 'sedang', 'poin_default' => 300, 'penanganan' => 'BK, Waka Kesis, Polsek, Pemanggilan Orang Tua, Surat Perjanjian', 'urutan' => 21, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'S22', 'nama_pelanggaran' => 'Membawa, merokok di sekolah/diluar sekolah memakai seragam sekolah', 'kategori' => 'sedang', 'poin_default' => 300, 'penanganan' => 'BK, Waka Kesis, Pemanggilan Orang Tua, Surat Perjanjian', 'urutan' => 22, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'S23', 'nama_pelanggaran' => 'Terlibat mogok belajar, aksi adu domba, provokasi', 'kategori' => 'sedang', 'poin_default' => 300, 'penanganan' => 'BK, Waka Kesis, Pemanggilan Orang Tua, Surat Perjanjian', 'urutan' => 23, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'B24', 'nama_pelanggaran' => 'Membawa senjata tajam atau sejenisnya tanpa izin guru', 'kategori' => 'berat', 'poin_default' => 400, 'penanganan' => 'BK, Waka Kesis, Pemanggilan Orang Tua, Surat Perjanjian', 'urutan' => 24, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'B25', 'nama_pelanggaran' => 'Merusak sarana dan prasarana sekolah', 'kategori' => 'berat', 'poin_default' => 400, 'penanganan' => 'BK, Waka Kesis, Pemanggilan Orang Tua, Surat Perjanjian', 'urutan' => 25, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'B26', 'nama_pelanggaran' => 'Terlibat tawuran, pengeroyokan, pengrusakan', 'kategori' => 'berat', 'poin_default' => 500, 'penanganan' => 'BK, Waka Kesis, Polsek, Pemanggilan Orang Tua, Surat Perjanjian', 'urutan' => 26, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'B27', 'nama_pelanggaran' => 'Terlibat pembuatan selebaran gelap', 'kategori' => 'berat', 'poin_default' => 500, 'penanganan' => 'BK, Waka Kesis, Polsek, Pemanggilan Orang Tua, Surat Perjanjian', 'urutan' => 27, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'B28', 'nama_pelanggaran' => 'Membawa, melihat, membuat atau mengedarkan konten pornografi', 'kategori' => 'berat', 'poin_default' => 700, 'penanganan' => 'BK, Waka Kesis, Pemanggilan Orang Tua, Surat Perjanjian', 'urutan' => 28, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'B29', 'nama_pelanggaran' => 'Mengejek, mencemooh dan melawan guru dan pegawai', 'kategori' => 'berat', 'poin_default' => 800, 'penanganan' => 'BK, Waka Kesis, Polsek, Pemanggilan Orang Tua, Surat Perjanjian', 'urutan' => 29, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'B30', 'nama_pelanggaran' => 'Perbuatan asusila (berzina, free sex, homo & lesbi, sodomi, pemerkosaan, aborsi, pelecehan seksual, pacaran diluar batas)', 'kategori' => 'berat', 'poin_default' => 1000, 'penanganan' => 'BK, Waka Kesis, Pemanggilan Orang Tua, Surat Pengunduran Diri', 'urutan' => 30, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'B31', 'nama_pelanggaran' => 'Memukul atau menghina guru dan pegawai sekolah', 'kategori' => 'berat', 'poin_default' => 1000, 'penanganan' => 'BK, Waka Kesis, Polsek, Pemanggilan Orang Tua, Surat Perjanjian', 'urutan' => 31, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'B32', 'nama_pelanggaran' => 'Terlibat aksi pemerasan, pencurian, jambret atau penadah hasil curian', 'kategori' => 'berat', 'poin_default' => 1000, 'penanganan' => 'BK, Waka Kesis, Polsek, Pemanggilan Orang Tua, Surat Perjanjian', 'urutan' => 32, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
                ['kode' => 'B33', 'nama_pelanggaran' => 'Terlibat pemerkosaan atau hamil di luar nikah', 'kategori' => 'berat', 'poin_default' => 1000, 'penanganan' => 'BK, Waka Kesis, Pemanggilan Orang Tua, Surat Pengunduran Diri', 'urutan' => 33, 'status_aktif' => true, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('bk_jenis_pelanggaran', function (Blueprint $table) {
            $table->dropIndex(['kategori']);
            $table->dropIndex(['urutan']);
            $table->dropColumn(['kategori', 'penanganan', 'urutan']);
        });
    }
};
