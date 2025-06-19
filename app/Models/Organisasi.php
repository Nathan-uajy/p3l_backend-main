<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Organisasi extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $primaryKey = 'organisasiID';

    protected $fillable = [
        'email', 'kontak', 'alamat', 'namaOrganisasi', 'password', 'role',
    ];

    protected $hidden = [
        'password',
    ];
}
