<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BkPelanggaranSiswa extends Model
{
    public const STATUS_BARU = 'Baru';
    public const STATUS_PROSES = 'Proses';
    public const STATUS_SELESAI = 'Selesai';

    protected $table = 'bk_pelanggaran_siswa';

    protected $fillable = [
        'data_siswa_id',
        'data_kelas_id',
        'data_tahun_pelajaran_id',
        'semester',
        'semester',
        'bk_jenis_pelanggaran_id',
        'tanggal',
        'poin',
        'status',
        'kronologi',
        'tindakan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_BARU,
            self::STATUS_PROSES,
            self::STATUS_SELESAI,
        ];
    }

    public function siswa()
    {
        return $this->belongsTo(DataSiswa::class, 'data_siswa_id');
    }

    public function kelas()
    {
        return $this->belongsTo(DataKelas::class, 'data_kelas_id');
    }

    public function tahunPelajaran()
    {
        return $this->belongsTo(DataTahunPelajaran::class, 'data_tahun_pelajaran_id');
    }

    public function jenis()
    {
        return $this->belongsTo(BkJenisPelanggaran::class, 'bk_jenis_pelanggaran_id');
    }
}
