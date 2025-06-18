<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailTransaksi extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_detail_transaksi';

    protected $fillable = [
        'transaksiID',
        'produkID',
    ];

    public $timestamps = true;

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksiID', 'transaksiID');
    }

    public function produk()
    {
        return $this->belongsTo(Barang::class, 'produkID', 'idProduk');
    }
}
