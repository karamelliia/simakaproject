<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataPembelajaran extends Model
{
    protected $table = 'data_pembelajaran';

    protected $fillable = [
        'data_kelas_id',
        'data_mapel_id',
        'guru_id',
    ];

    // 🔗 ke kelas
    public function kelas()
    {
        return $this->belongsTo(DataKelas::class, 'data_kelas_id');
    }

    // 🔗 ke mapel
    public function mapel()
    {
        return $this->belongsTo(DataMapel::class, 'data_mapel_id');
    }

    // 🔗 ke guru (pengguna)
    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    // 🔗 ke nilai
    // public function nilai()
    // {
    //     return $this->hasMany(LegerNilai::class, 'data_pembelajaran_id');
    // }
}
