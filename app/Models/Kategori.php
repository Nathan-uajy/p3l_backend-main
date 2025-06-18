<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    protected $table = 'kategori';
    protected $primaryKey = 'idKategori';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'namaKategori',
    ];

    public function barangs()
    {
        return $this->hasMany(Barang::class, 'kategoriID', 'idKategori');
    }
}