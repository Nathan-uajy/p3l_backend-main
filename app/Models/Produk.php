<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'produk';
    protected $primaryKey = 'idProduk';
    public $timestamps = false;

    protected $fillable = [
        'donasiID', 'penitipID', 'namaProduk',  'deskripsi',
        'harga', 'kategori', 'status', 'tglMulai',
        'tglSelesai', 'garansi', 'gambar',
    ];

    
}