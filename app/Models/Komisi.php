<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Komisi extends Model
{
    use HasFactory;

    protected $primaryKey = 'komisiID';
    
    protected $fillable = [
        'transaksiID',
        'penitipID',
        'komisi_hunter',
        'komsi_perusahaan',
        'jumlahKomisi',
        'persentase',
        'tanggalKomisi',
    ];

    // Relasi ke Transaksi
    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksiID', 'transaksiID');
    }

    // Relasi ke Penitip
    public function penitip()
    {
        return $this->belongsTo(Penitip::class, 'penitipID', 'penitipID');
    }
}
