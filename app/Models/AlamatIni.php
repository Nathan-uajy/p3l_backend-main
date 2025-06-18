<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlamatIni extends Model
{
    protected $table = 'alamats_ini';
    protected $primaryKey = 'alamatID';
    public $timestamps = true;

    protected $fillable = [
        'pembeliID',
        'namaAlamat',
        'namaPenerima',
        'noHpPenerima',
        'alamat',
        'kodePos',
    ];

    // Relasi ke model Pembeli
    public function pembeli()
    {
        return $this->belongsTo(Pembeli::class, 'pembeliID');
    }
}
