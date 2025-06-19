<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    // Nama tabel defaultnya adalah 'user_active_cart_items', jika Anda menggunakan nama lain, set $table
    // public $timestamps = true; // default, jadi tidak perlu ditulis jika true

    protected $table = 'cart';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'product_id', // Ini akan merujuk ke produks.idProduk
        'price_at_add',
    ];

    public function pembeli()
    {
        // Asumsi model User Anda adalah App\Models\User dengan primary key 'id'
        return $this->belongsTo(Pembeli::class, 'user_id', 'pembeliID');
    }

    public function produkk()
    {
        // Merujuk ke model Produk Anda dengan foreign key 'product_id' dan owner key 'idProduk'
        return $this->belongsTo(Barang::class, 'product_id', 'idProduk');
    }
}