<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaksi;

class NotaPembeliController extends Controller
{
    // Daftar transaksi dengan status siap diambil
    public function daftarTransaksi()
    {
        $transaksis = Transaksi::where('status', 'siap diambil')
            ->with('pembeli')
            ->get();

        return response()->json($transaksis);
    }

    // Ambil detail transaksi + perhitungan total, potongan poin, dsb
    public function getNotaData($transaksiID)
    {
        $transaksi = Transaksi::with([
            'pembeli',
            'alamat',
            'detailTransaksis.produk',
            'penjadwalan' => function ($q) {
                $q->where('tipe', 'pengambilan');
            },
            'penjadwalan.pegawai'
        ])->findOrFail($transaksiID);

        // Hitung total harga produk
        $total = $transaksi->detailTransaksis->sum(function ($dt) {
            return $dt->produk->harga ?? 0;
        });

        // Cek poin pembeli
        $poinPembeli = $transaksi->pembeli->poinLoyalitas ?? 0;

        // Potongan dari poin (1 poin = Rp100)
        $nilaiPerPoin = 100;
        $maksPoinDigunakan = min($poinPembeli, floor($total / $nilaiPerPoin));
        $potongan = $maksPoinDigunakan * $nilaiPerPoin;

        // Total bayar setelah potongan
        $totalBayar = $total - $potongan;

        // Hitung poin reward
        $poin = floor($totalBayar / 10000);  // 1 poin per Rp10rb
        $bonus = $total > 500000 ? floor($poin * 0.2) : 0;
        $totalPoin = $poin + $bonus;

        // Format response
        return response()->json([
            'transaksiID' => $transaksi->transaksiID,
            'waktu_transaksi' => $transaksi->waktu_transaksi,
            'pembeli' => $transaksi->pembeli,
            'alamat' => $transaksi->alamat,
            'penjadwalan' => $transaksi->penjadwalan,
            'detail_transaksis' => $transaksi->detailTransaksis,
            'total' => $total,
            'poinDigunakan' => $maksPoinDigunakan,
            'potongan' => $potongan,
            'totalBayar' => $totalBayar,
            'poinReward' => $totalPoin
        ]);
    }
}
