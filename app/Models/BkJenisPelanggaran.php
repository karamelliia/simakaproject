<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BkJenisPelanggaran extends Model
{
    public const KATEGORI_RINGAN = 'ringan';
    public const KATEGORI_SEDANG = 'sedang';
    public const KATEGORI_BERAT = 'berat';
    public const KATEGORI_SANGAT_BERAT = 'sangat_berat';

    protected $table = 'bk_jenis_pelanggaran';

    protected $fillable = [
        'kode',
        'nama_pelanggaran',
        'kategori',
        'poin_default',
        'is_terminal',
        'affects_pkl_score',
        'penanganan',
        'urutan',
        'status_aktif',
    ];

    protected $casts = [
        'status_aktif' => 'boolean',
        'is_terminal' => 'boolean',
        'affects_pkl_score' => 'boolean',
    ];

    public static function kategoriOptions(): array
    {
        return [
            self::KATEGORI_RINGAN,
            self::KATEGORI_SEDANG,
            self::KATEGORI_BERAT,
            self::KATEGORI_SANGAT_BERAT,
        ];
    }

    public static function kategoriLabels(): array
    {
        return [
            self::KATEGORI_RINGAN => 'Pelanggaran Ringan',
            self::KATEGORI_SEDANG => 'Pelanggaran Sedang',
            self::KATEGORI_BERAT => 'Pelanggaran Berat',
            self::KATEGORI_SANGAT_BERAT => 'Pelanggaran Sangat Berat',
        ];
    }

    public function pelanggaranSiswa()
    {
        return $this->hasMany(BkPelanggaranSiswa::class, 'bk_jenis_pelanggaran_id');
    }
}
