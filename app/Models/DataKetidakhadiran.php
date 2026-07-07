<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataKetidakhadiran extends Model
{
    protected $table = 'data_ketidakhadiran';
        protected $fillable = [
            'data_siswa_id',
            'data_tahun_pelajaran_id',
            'semester',
            'sakit',
            'izin',
            'tanpa_keterangan',
        ];

    public function siswa()
    {
        return $this->belongsTo(DataSiswa::class, 'data_siswa_id');
    }

    public function tahunPelajaran()
    {
        return $this->belongsTo(DataTahunPelajaran::class, 'data_tahun_pelajaran_id');
    }
}
