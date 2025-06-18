<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Pembeli extends Authenticatable
{
    use HasApiTokens, Notifiable;

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



    public function alamats()
    {
        return $this->hasMany(Alamat::class, 'user_id', 'pembeliID');
    }

    public function activeCartItems()
    {
        return $this->hasMany(Cart::class, 'user_id', 'pembeliID');
        // Atau model Cart jika Anda menamainya Cart:
        // return $this->hasMany(Cart::class, 'user_id', 'pembeliID');
    }
}

