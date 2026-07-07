<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Peran;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'pengguna';

    protected $fillable = [
        'peran_id',
        'nama',
        'email',
        'password',
        'foto',
        'status_aktif',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function peran()
    {
        return $this->belongsTo(Peran::class, 'peran_id');
    }

    public function rolesTambahan()
    {
        return $this->belongsToMany(
            Peran::class,
            'pengguna_peran',
            'pengguna_id',
            'peran_id'
        );
    }

    public function dataGuru()
    {
        return $this->hasOne(DataGuru::class, 'pengguna_id');
    }

    public function hasRole($roleName)
    {
        // cek role utama
        if ($this->peran && $this->peran->nama_peran === $roleName) {
            return true;
        }

        // cek role tambahan
        return $this->rolesTambahan()
            ->where('nama_peran', $roleName)
            ->exists();
    }

    public function bkDibuat()
    {
        return $this->hasMany(DataBk::class, 'created_by');
    }

    public function bkDiubah()
    {
        return $this->hasMany(DataBk::class, 'updated_by');
    }
}
