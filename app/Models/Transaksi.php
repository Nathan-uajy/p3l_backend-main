<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $primaryKey = 'transaksiID';
    protected $table = 'transaksis';

    protected $fillable = [
        'pembeliID',
        'penitipID',
        'tipe_transaksi',
        'status',
        'waktu_transaksi',
        'totalHarga',
        'poinEarned'
    ];

    // Cast attributes to proper types
    protected $casts = [
        'waktu_transaksi' => 'datetime',
        'totalHarga' => 'integer',
        'poinEarned' => 'integer',
    ];

    public $timestamps = true;

    public function detailTransaksis()
    {
        return $this->hasMany(DetailTransaksi::class, 'transaksiID', 'transaksiID');
    }

    public function penjadwalan()
    {
        return $this->hasOne(Penjadwalan::class, 'transaksiID');
    }

    public function pembeli()
    {
        return $this->belongsTo(Pembeli::class, 'pembeliID', 'pembeliID');
    }

    public function penitip()
    {
        return $this->belongsTo(Penitip::class, 'penitipID', 'penitipID');
    }

    // Calculate total harga from detail transaksi
    public function getTotalHargaAttribute()
    {
        // If totalHarga is already set in database, return it
        if (isset($this->attributes['totalHarga']) && $this->attributes['totalHarga'] > 0) {
            return $this->attributes['totalHarga'];
        }
        
        // Otherwise calculate from details
        return $this->detailTransaksis->sum(function ($detail) {
            return $detail->getSubtotalAttribute();
        }) ?? 0;
    }

    // Calculate poin earned
    public function getPoinEarnedAttribute()
    {
        // If poinEarned is already set in database, return it
        if (isset($this->attributes['poinEarned']) && $this->attributes['poinEarned'] > 0) {
            return $this->attributes['poinEarned'];
        }
        
        // Otherwise calculate from total
        $totalHarga = $this->getTotalHargaAttribute();
        $basePoints = (int)($totalHarga / 10000); // 1 point per Rp10,000
        
        if ($totalHarga > 500000) {
            $bonusPoints = (int)($basePoints * 0.20); // 20% bonus
            return $basePoints + $bonusPoints;
        }
        
        return $basePoints;
    }
}