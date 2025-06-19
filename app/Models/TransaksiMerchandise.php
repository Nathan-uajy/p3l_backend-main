<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TransaksiMerchandise extends Model
{
    protected $table = 'transaksimerchandise';
    protected $primaryKey = 'penukaranID';
    public $timestamps = true;

    protected $fillable = [
        'pembeliID',
        'merchandiseID',
        'jumlah_penukaran',
        'jumlah_poin',
        'tanggal_claim',
        'tanggal_ambil',
    ];

    protected $casts = [
        'tanggal_claim' => 'date:Y-m-d',
        'tanggal_ambil' => 'date:Y-m-d',
    ];

    // Mutator untuk tanggal_claim
    public function setTanggalClaimAttribute($value)
    {
        if ($value) {
            $this->attributes['tanggal_claim'] = Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
        }
    }

    // Mutator untuk tanggal_ambil
    public function setTanggalAmbilAttribute($value)
    {
        if ($value) {
            $this->attributes['tanggal_ambil'] = Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
        } else {
            $this->attributes['tanggal_ambil'] = null;
        }
    }

    // Accessor untuk tanggal_claim
    public function getTanggalClaimAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        }
        return $value;
    }

    // Accessor untuk tanggal_ambil
    public function getTanggalAmbilAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        }
        return $value;
    }

    public function pembeli()
    {
        return $this->belongsTo(Pembeli::class, 'pembeliID');
    }

    public function merchandise()
    {
        return $this->belongsTo(Merchandise::class, 'merchandiseID');
    }
}