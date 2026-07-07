<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataKelas extends Model
{
    protected $table = 'data_kelas';

    protected $fillable = [
        'data_sekolah_id',
        'data_tahun_pelajaran_id',
        'nama_kelas',
        'tingkat',
        'jurusan_id',
        'wali_kelas_id',
    ];

    public function tahunPelajaran()
    {
        return $this->belongsTo(
            DataTahunPelajaran::class,
            'data_tahun_pelajaran_id'
        );
    }

  
    public function wali()
    {
        return $this->belongsTo(DataGuru::class, 'wali_kelas_id', 'pengguna_id');
    }

    public function siswa()
    {
        return $this->hasMany(
            DataSiswa::class,
            'data_kelas_id'
        );
    }
    public function nilaiMapel()
    {
        return $this->hasMany(NilaiMapelSiswa::class, 'data_kelas_id');
    }
    public function jurusan()
    {
        return $this->belongsTo(\App\Models\DataJurusan::class, 'jurusan_id');
    }

    public function jadwalPelajaran()
    {
        return $this->hasMany(JadwalPelajaran::class, 'data_kelas_id');
    }

    public function absensiJam()
    {
        return $this->hasMany(AbsensiJamSiswa::class, 'data_kelas_id');
    }

    public function riwayatSebagaiKelasAsal()
    {
        return $this->hasMany(StudentClassHistory::class, 'kelas_asal_id');
    }

    public function riwayatSebagaiKelasTujuan()
    {
        return $this->hasMany(StudentClassHistory::class, 'kelas_tujuan_id');
    }

    public function dataBk()
    {
        return $this->hasMany(DataBk::class, 'data_kelas_id');
    }

    public function pelanggaranBk()
    {
        return $this->hasMany(BkPelanggaranSiswa::class, 'data_kelas_id');
    }

    public function pembinaanBk()
    {
        return $this->hasMany(BkPembinaanSiswa::class, 'data_kelas_id');
    }

    public function homeVisitBk()
    {
        return $this->hasMany(BkHomeVisit::class, 'data_kelas_id');
    }

    public function pemanggilanOrtuBk()
    {
        return $this->hasMany(BkPemanggilanOrangTua::class, 'data_kelas_id');
    }

    public function perjanjianBk()
    {
        return $this->hasMany(BkPerjanjianSiswa::class, 'data_kelas_id');
    }

    public function pengunduranDiriBk()
    {
        return $this->hasMany(BkPengunduranDiri::class, 'data_kelas_id');
    }

    public function peminatanBk()
    {
        return $this->hasMany(BkPeminatanSiswa::class, 'data_kelas_id');
    }

    public function sikapBk()
    {
        return $this->hasMany(BkSikapSiswa::class, 'data_kelas_id');
    }
}
