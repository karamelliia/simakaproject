<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataSiswa extends Model
{
     protected $table = 'data_siswa';
        protected $fillable = [
            'data_kelas_id',
            'nama_siswa',
            'nis',
            'nisn',
            'tempat_lahir',
            'tanggal_lahir',
            'jenis_kelamin',
            'agama',
            'status_dalam_keluarga',
            'anak_ke',
            'tinggi_badan',
            'berat_badan',
            'penyakit_bawaan',
            'alamat',
            'telepon',
            'sekolah_asal',
            'diterima_di_kelas',
            'tanggal_diterima',
            'nama_ayah',
            'pekerjaan_ayah',
            'nama_ibu',
            'pekerjaan_ibu',
            'alamat_orang_tua',
            'telepon_orang_tua',
            'nama_wali',
            'pekerjaan_wali',
            'alamat_wali',
            'telepon_wali',
            'status_siswa',
            'tahun_lulus',
        ];
    public function kelas()
    {
        return $this->belongsTo(DataKelas::class, 'data_kelas_id');
    }

    public function nilai()
    {
        return $this->hasMany(LegerNilai::class, 'data_siswa_id');
    }

    // 🔗 relasi ke kehadiran
    public function kehadiran()
    {
        return $this->hasOne(DataKetidakhadiran::class, 'data_siswa_id');
    }

    // 🔗 relasi ke catatan wali kelas
    public function catatanWali()
    {
        return $this->hasOne(CatatanWaliKelas::class, 'data_siswa_id');
    }

    public function nilaiMapel()
    {
        return $this->hasMany(NilaiMapelSiswa::class, 'data_siswa_id');
    }

    public function absensiJam()
    {
        return $this->hasMany(AbsensiJamSiswa::class, 'data_siswa_id');
    }

    public function dataBk()
    {
        return $this->hasMany(DataBk::class, 'data_siswa_id');
    }

    public function pelanggaranBk()
    {
        return $this->hasMany(BkPelanggaranSiswa::class, 'data_siswa_id');
    }

    public function pembinaanBk()
    {
        return $this->hasMany(BkPembinaanSiswa::class, 'data_siswa_id');
    }

    public function homeVisitBk()
    {
        return $this->hasMany(BkHomeVisit::class, 'data_siswa_id');
    }

    public function pemanggilanOrtuBk()
    {
        return $this->hasMany(BkPemanggilanOrangTua::class, 'data_siswa_id');
    }

    public function perjanjianBk()
    {
        return $this->hasMany(BkPerjanjianSiswa::class, 'data_siswa_id');
    }

    public function pengunduranDiriBk()
    {
        return $this->hasMany(BkPengunduranDiri::class, 'data_siswa_id');
    }

    public function peminatanBk()
    {
        return $this->hasMany(BkPeminatanSiswa::class, 'data_siswa_id');
    }

    public function sikapBk()
    {
        return $this->hasMany(BkSikapSiswa::class, 'data_siswa_id');
    }

    public function tracerStudy()
    {
        return $this->hasOne(TracerStudy::class, 'data_siswa_id');
    }

    public function riwayatKelas()
    {
        return $this->hasMany(StudentClassHistory::class, 'data_siswa_id');
    }

    public function keputusanAkhirTahun()
    {
        return $this->hasMany(StudentYearEndDecision::class, 'data_siswa_id');
    }

    public function dataKeluar()
    {
        return $this->hasOne(StudentExit::class, 'data_siswa_id');
    }
}
