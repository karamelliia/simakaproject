<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalPelajaran extends Model
{
    protected $table = 'jadwal_pelajaran';

    protected $fillable = [
        'data_tahun_pelajaran_id',
        'data_kelas_id',
        'data_mapel_id',
        'guru_id',
        'hari',
        'jam_ke',
    ];

    public function tahunPelajaran()
    {
        return $this->belongsTo(DataTahunPelajaran::class, 'data_tahun_pelajaran_id');
    }

    public function kelas()
    {
        return $this->belongsTo(DataKelas::class, 'data_kelas_id');
    }

    public function mapel()
    {
        return $this->belongsTo(DataMapel::class, 'data_mapel_id');
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }
}

