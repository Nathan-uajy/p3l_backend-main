<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Pegawai extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'nama', 'email', 'password', 'role', 'jabatan', 'fcm_token'
    ];

    protected $hidden = [
        'password',
    ];

    protected $primaryKey = 'pegawaiID';
    public $incrementing = true;
    protected $keyType = 'int';

    public function penjadwalans()
    {
        return $this->hasMany(Penjadwalan::class, 'pegawaiID');
    }

}
