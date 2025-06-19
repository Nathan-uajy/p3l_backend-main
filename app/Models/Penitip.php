<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Penitip extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'penitips';
    protected $primaryKey = 'penitipID';

    protected $fillable = [
        'nama', 'email', 'password', 'nomorHP', 'alamat', 'saldo',
        'poinLoyalitas', 'role', 'nik', 'foto_ktp', 'isTopSeller', 'topSellerUntil',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'isTopSeller' => 'boolean',
        'topSellerUntil' => 'date',
        'saldo' => 'decimal:2',
        'poinLoyalitas' => 'integer',
    ];

    public function barangs()
    {
        return $this->hasMany(Barang::class, 'penitipID', 'penitipID');
    }
}