<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barangs';
    protected $primaryKey = 'idProduk';

    protected $fillable = [
        'donasiID',
        'penitipID',
        'namaProduk',
        'gambar',
        'gambar2',
        'deskripsi',
        'harga',
        'kategoriID', 
        'status',
        'tglMulai',
        'tglSelesai',
        'garansi',
        'kurirID',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
        'tglMulai' => 'date',
        'tglSelesai' => 'date',
        'garansi' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function penitip()
    {
        return $this->belongsTo(Penitip::class, 'penitipID', 'penitipID');
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategoriID', 'idKategori');
    }

    public function kurir()
    {
        return $this->belongsTo(Pegawai::class, 'kurirID', 'pegawaiID');
    }
}