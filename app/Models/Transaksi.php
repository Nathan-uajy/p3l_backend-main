<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $primaryKey = 'transaksiID';

    protected $fillable = [
        'pembeliID',
        'penitipID',
        'alamatID',
        'tipe_transaksi',
        'status',
        'waktu_transaksi'
    ];

    public $timestamps = true;

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
