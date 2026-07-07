<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataTahunPelajaran extends Model
{
    protected $table = 'data_tahun_pelajaran';

    protected $fillable = [
        'tahun_pelajaran',
        'semester',
        'tempat_pembagian_rapor',
        'tanggal_pembagian_rapor',
        'status_aktif',
    ];

    protected $casts = [
        'status_aktif' => 'boolean',
        'tanggal_pembagian_rapor' => 'date',
    ];

    public function nilaiMapel()
    {
        return $this->hasMany(NilaiMapelSiswa::class, 'data_tahun_pelajaran_id');
    }

    public function dataBk()
    {
        return $this->hasMany(DataBk::class, 'data_tahun_pelajaran_id');
    }
}
