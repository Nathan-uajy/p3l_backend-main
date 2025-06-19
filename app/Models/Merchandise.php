<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merchandise extends Model
{
    protected $primaryKey = 'merchandiseID';

    protected $fillable = [
        'nama', 'stok', 'hargaMerch', 'foto',
    ];
}
