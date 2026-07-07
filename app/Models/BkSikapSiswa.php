<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BkSikapSiswa extends Model
{
    public const PREDIKAT_SANGAT_BAIK = 'Sangat Baik';
    public const PREDIKAT_BAIK = 'Baik';
    public const PREDIKAT_CUKUP = 'Cukup';
    public const PREDIKAT_PERLU_BIMBINGAN = 'Perlu Bimbingan';

    public const STATUS_MONITORING = 'Perlu Monitoring';
    public const STATUS_PEMBINAAN = 'Perlu Pembinaan';
    public const STATUS_STABIL = 'Stabil';

    protected $table = 'bk_sikap_siswa';

    protected $fillable = [
        'data_siswa_id',
        'data_kelas_id',
        'data_tahun_pelajaran_id',
        'semester',
        'semester',
        'tanggal_penilaian',
        'aspek_sikap',
        'predikat',
        'status',
        'catatan',
        'tindak_lanjut',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal_penilaian' => 'date',
    ];

    public static function predikatOptions(): array
    {
        return [
            self::PREDIKAT_SANGAT_BAIK,
            self::PREDIKAT_BAIK,
            self::PREDIKAT_CUKUP,
            self::PREDIKAT_PERLU_BIMBINGAN,
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_MONITORING,
            self::STATUS_PEMBINAAN,
            self::STATUS_STABIL,
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
}
