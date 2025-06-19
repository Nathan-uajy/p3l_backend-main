<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailTransaksi extends Model
{
    use HasFactory;

    protected $table = 'detail_transaksis'; // Sesuaikan jika nama tabel Anda berbeda
    protected $primaryKey = 'id_detail_transaksi';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false; // Asumsi tidak ada created_at/updated_at di tabel ini

    /**
     * Atribut yang dapat diisi secara massal.
     * Hanya ID yang relevan untuk disimpan dari sisi aplikasi.
     * Kuantitas diasumsikan 1 karena barang bekas unik.
     */
    protected $fillable = [
        'transaksiID',
        'produkID',
        // 'kuantitas', // Bisa ditambahkan jika Anda ingin eksplisit menyimpan 1
    ];

    /**
     * Relasi ke model Transaksi (parent).
     */
    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksiID', 'idTransaksi');
    }

    /**
     * Relasi ke model Produk.
     */
    public function produk()
    {
        // 'produkID' adalah foreign key di tabel ini (detail_transaksis)
        // 'idProduk' adalah primary key di tabel 'produks'
        return $this->belongsTo(Produk::class, 'produkID', 'idProduk');
    }
}