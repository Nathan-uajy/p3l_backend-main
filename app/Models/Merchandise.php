<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merchandise extends Model
{
    protected $table = 'merchandise';
    protected $primaryKey = 'merchandiseID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nama', 'stock', 'jumlahPoin', 'foto',
    ];

    protected $casts = [
        'jumlahPoin' => 'integer',
        'stock' => 'integer',
    ];

    public function transaksiMerchandise()
    {
        return $this->hasMany(TransaksiMerchandise::class, 'merchandiseID', 'merchandiseID');
    }
}
