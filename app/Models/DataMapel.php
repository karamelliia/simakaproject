<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataMapel extends Model
{
    protected $table = 'data_mapel';

    protected $fillable = [
        'nama_mapel',
        'singkatan',
        'urutan_cetak',
        'kelompok_mapel',
        'tingkat',
        'jurusan_id'
    ];

    // Relasi (dipakai nanti di pembelajaran)
    public function pembelajaran()
    {
        return $this->hasMany(DataPembelajaran::class, 'data_mapel_id');
    }

    public function nilaiSiswa()
    {
        return $this->hasMany(NilaiMapelSiswa::class, 'data_mapel_id');
    }

    public function jurusan()
    {
        return $this->belongsTo(\App\Models\DataJurusan::class, 'jurusan_id');
    }
}
