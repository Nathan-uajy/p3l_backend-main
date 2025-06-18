<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Pembeli extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'pembelis';
    protected $primaryKey = 'pembeliID';


    protected $fillable = [
        'nama', 'email', 'password', 'nomorHP', 'alamat', 'poinLoyalitas', 'alamatID', 'ulasanID', 'role', 'fcm_token'
    ];

    protected $hidden = [
        'password',
    ];

    public function alamatsIni()
    {
        return $this->hasMany(AlamatIni::class, 'pembeliID');
    }


}

