<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataSekolah extends Model
{
   protected $table = 'data_sekolah';

    protected $fillable = [
        'nama_sekolah',
        'npsn',
        'kode_pos',
        'telepon',
        'alamat',
        'desa',
        'kecamatan',
        'kota',
        'provinsi',
        'email',
        'website',
        'kepala_sekolah',
        'nip_kepala_sekolah',
        'logo',
    ];

    public function kelas()
    {
        return $this->hasMany(DataKelas::class, 'data_sekolah_id');
    }
}
