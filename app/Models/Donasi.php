<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donasi extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     */
    protected $table = 'donasis';

    /**
     * Nama primary key.
     */
    protected $primaryKey = 'donasiID';

    /**
     * Atribut yang dapat diisi secara massal.
     * Sesuaikan dengan nama kolom di migration Anda.
     */
    protected $fillable = [
        'produkID',
        'organisasiID',
        'namaPenerima',
        'tanggal_donasi',
        'jumlah',
    ];

    /**
     * Tentukan tipe data untuk kolom tertentu.
     */
    protected $casts = [
        'tanggal_donasi' => 'date',
    ];

    /**
     * Definisikan relasi "belongsTo": setiap Donasi terkait dengan satu Produk.
     */
    public function produk()
    {
        return $this->belongsTo(Barang::class, 'produkID', 'idProduk');
    }

    /**
     * Definisikan relasi "belongsTo": setiap Donasi diberikan ke satu Organisasi.
     */
    public function organisasi()
    {
        return $this->belongsTo(Organisasi::class, 'organisasiID', 'organisasiID');
    }
}
