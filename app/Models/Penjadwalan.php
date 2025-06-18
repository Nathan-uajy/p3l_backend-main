<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjadwalan extends Model
{
    protected $primaryKey = 'penjadwalanID';
    protected $table = 'penjadwalans';

    protected $fillable = [
        'transaksiID',
        'pegawaiID',
        'tipe',
        'status',
        'tanggal',
        'waktu',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksiID');
    }

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'pegawaiID');
    }

    
}
