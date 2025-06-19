<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diskusi extends Model
{
    protected $table = 'diskusis';
    protected $primaryKey = 'diskusiID';
    protected $fillable = ['isi', 'tanggal', 'pegawaiID', 'pembeliID', 'produkID'];
    public $timestamps = false;

    // Relasi ke pembeli
    // public function pembeli()
    // {
    //     return $this->belongsTo(Pembeli::class, 'pembeliID', 'pembeliID');
    // }

    // // Relasi ke pegawai
    // public function pegawai()
    // {
    //     return $this->belongsTo(Pegawai::class, 'pegawaiID', 'pegawaiID');
    // }

    // // Akses nama penulis (bisa pembeli atau pegawai)
    // public function getPenulisAttribute()
    // {
    //     if ($this->pembeli) {
    //         return $this->pembeli->nama; // sesuaikan dengan kolom nama di tabel pembeli
    //     } elseif ($this->pegawai) {
    //         return $this->pegawai->nama; // sesuaikan dengan kolom nama di tabel pegawai
    //     } else {
    //         return 'Tidak Diketahui';
    //     }
    // }

    // // Akses role penulis
    // public function getRoleAttribute()
    // {
    //     if ($this->pembeli) {
    //         return 'pembeli';
    //     } elseif ($this->pegawai) {
    //         return 'cs';
    //     } else {
    //         return 'unknown';
    //     }
    // }
}
