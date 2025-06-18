<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailTransaksi extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_detail_transaksi';
    protected $table = 'detail_transaksis';

    protected $fillable = [
        'transaksiID',
        'produkID',
        'jumlah',
        'harga_satuan',
        'subtotal'
    ];

    // Cast attributes to proper types
    protected $casts = [
        'jumlah' => 'integer',
        'harga_satuan' => 'integer',
        'subtotal' => 'integer',
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

    // Calculate subtotal
    public function getSubtotalAttribute()
    {
        // If subtotal is already set in database, return it
        if (isset($this->attributes['subtotal']) && $this->attributes['subtotal'] > 0) {
            return $this->attributes['subtotal'];
        }
        
        // Otherwise calculate it
        return ($this->jumlah ?? 1) * ($this->harga_satuan ?? 0);
    }
}