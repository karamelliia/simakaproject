<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsensiJamSiswa extends Model
{
    protected $table = 'absensi_jam_siswa';

    protected $fillable = [
        'tanggal',
        'data_tahun_pelajaran_id',
        'semester',
        'data_kelas_id',
        'data_mapel_id',
        'guru_id',
        'data_siswa_id',
        'hari',
        'jam_ke',
        'status',
        'catatan',
    ];

    public function siswa()
    {
        return $this->belongsTo(DataSiswa::class, 'data_siswa_id');
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

