<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Penitip extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $primaryKey = 'penitipID';

    protected $fillable = [
        'nama', 'email', 'password', 'nomorHP', 'alamat', 'saldo',
        'poinLoyalitas', 'role', 'nik', 'foto_ktp', 'fcm_token'
    ];

    protected $hidden = [
        'password',
    ];
}

