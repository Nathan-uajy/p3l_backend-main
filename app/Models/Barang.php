<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barangs';
    protected $primaryKey = 'idProduk'; // karena bukan "id"

    protected $fillable = [
        'donasiID',
        'penitipID',
        'namaProduk',
        'gambar',
        'gambar2',
        'deskripsi',
        'harga',
        'kategori',
        'status',
        'tglMulai',
        'tglSelesai',
        'garansi',
    ];

    protected $dates = [
        'tglMulai',
        'tglSelesai',
        'garansi',
    ];
    public function penitip()
    {
        return $this->belongsTo(Penitip::class, 'penitipID', 'penitipID');
    }

    
}
