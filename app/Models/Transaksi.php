<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksis';
    protected $primaryKey = 'idTransaksi';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false; // Karena Anda punya tanggalTransaksi

    /**
     * Atribut yang dapat diisi secara massal, HANYA kolom dari gambar tabel transaksis.
     */
    protected $fillable = [
        // 'penjadwalanID',
        'pembeliID',
        'penitipID',   
        'alamatID',     // Ini ID Pembeli
        'totalHarga',
        'status',
        'tanggalTransaksi',
        'metodePembayaran',
        'biayaPengiriman',
        'diskon',
        'buktiPembayaran'
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     */
    protected $casts = [
        'tanggalTransaksi' => 'date', // Cast ke objek Carbon (date)
        'totalHarga'       => 'float',
        'biayaPengiriman'  => 'float',
        'diskon'           => 'float',
        'penjadwalanID'    => 'integer', // Sesuaikan jika tipe datanya berbeda
        'pegawaiID'        => 'integer', // Sesuaikan jika tipe datanya berbeda
    ];

    /**
     * Relasi ke item-item transaksi (DetailTransaksi).
     */
    public function items()
    {
        return $this->hasMany(DetailTransaksi::class, 'transaksiID', 'idTransaksi');
    }

    public function detailTransaksis()
    {
        return $this->hasMany(DetailTransaksi::class, 'transaksiID', 'transaksiID');
    }

    public function penjadwalan()
    {
        return $this->hasOne(Penjadwalan::class, 'transaksiID')->where('tipe', 'pengiriman');
    }

    public function pembeli()
    {
        return $this->belongsTo(Pembeli::class, 'pembeliID');
    }

    public function penitip()
    {
        return $this->belongsTo(Penitip::class, 'penitipID');
    }

    public function alamat()
    {
        return $this->belongsTo(\App\Models\AlamatIni::class, 'alamatID', 'alamatID');
    }


    public function penjadwalanPengiriman()
    {
        return $this->hasOne(Penjadwalan::class, 'transaksiID')->where('tipe', 'pengiriman');
    }
}