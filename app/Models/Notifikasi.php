<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    use HasFactory;
    public function penitip()
    {
        return $this->belongsTo(Penitip::class, 'penitipID');
    }

    public function pembeli()
    {
        return $this->belongsTo(Pembeli::class, 'pembeliID');
    }
}
