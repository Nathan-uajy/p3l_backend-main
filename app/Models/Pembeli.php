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
    public $incrementing = true;
    protected $keyType = 'int';


    protected $fillable = [
        'nama', 'email', 'password', 'nomorHP', 'alamat', 'poinLoyalitas', 'alamatID', 'ulasanID', 'role',
    ];

    protected $casts = [
        'poinLoyalitas' => 'integer',
    ];

    protected $hidden = [
        'password',
    ];

    public function alamatsIni()
    {
        return $this->hasMany(AlamatIni::class, 'pembeliID');
    }

    public function transaksiMerchandise()
    {
        return $this->hasMany(TransaksiMerchandise::class, 'pembeliID', 'pembeliID');
    }

    public function transaksis() 
    {
        return $this->hasMany(Transaksi::class, 'pembeliID', 'pembeliID');
    }


}

