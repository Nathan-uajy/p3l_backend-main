<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'produks';
    protected $primaryKey = 'idProduk';
    public $timestamps = false;

    protected $fillable = [
        'donasiID', 'penitipID', 'namaProduk',  'deskripsi',
        'harga', 'kategori', 'status', 'tglMulai',
        'tglSelesai', 'garansi', 'gambar', 
        'cart_holder_user_id'
    ];

    // Produk.php
    public function diskusis() {
        return $this->hasMany(Diskusi::class, 'idProduk', 'produkID');
    }

    // Relasi ke User yang memegang produk di keranjang
    public function cartHolder()
    {
        // Asumsi model User Anda ada di App\Models\User dan primary key-nya 'id'
        return $this->belongsTo(User::class, 'cart_holder_user_id', 'id');
    }

    // Scope untuk produk yang tersedia
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available'); // 'available' adalah salah satu nilai di kolom status Anda
    }

    // Helper untuk mendapatkan URL gambar jika 'gambar' hanya nama file
    public function getGambarUrlAttribute()
    {
        if ($this->gambar) {
            // Asumsi gambar disimpan di public/storage/images/produks/
            // atau sesuaikan path-nya
            return asset('storage/images/produks/' . $this->gambar);
        }
        return asset('images/placeholder.jpg'); // Gambar placeholder default
    }

    public function penitip()
    {
        return $this->belongsTo(Penitip::class, 'penitip_id', 'penitipID');
    }

}