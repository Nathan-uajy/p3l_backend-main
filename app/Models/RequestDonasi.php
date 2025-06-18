<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestDonasi extends Model
{
    use HasFactory;

    protected $table = 'request_donasi';
    protected $primaryKey = 'idReqDonasi';

    protected $fillable = [
        'namaReqDonasi',
        'kategoriReqDonasi',
        'organisasiID',
        'donasiID',
    ];

    public function organisasi()
    {
        // Sesuaikan 'App\Models\Organisasi' dan 'organisasi_id' jika perlu
        return $this->belongsTo(Organisasi::class, 'organisasiID');
    }
}
