<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alamat extends Model
{
    use HasFactory;

    protected $table = 'alamats';

    protected $primaryKey = 'alamatID';

    protected $fillable = [
        'user_id',
        'namaAlamat',
        'namaPenerima',
        'noHpPenerima',
        'alamat',
        'kodePos',
    ];
    public $timestamps = true;

    // app/Models/Alamat.php

    public function pembeli()
    {
        return $this->belongsTo(Pembeli::class, 'pembeliID');
    }

}
