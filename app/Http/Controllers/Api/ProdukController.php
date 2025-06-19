<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    public function index()
    {
        return response()->json(Produk::all(), 200);
    }

    public function show($id)
    {
        // Cari produk berdasarkan ID
        // $produk = Produk::find($id);
        $produk = Produk::where('idProduk', $id)->first();

        if (!$produk) {
            return response()->json([
                'message' => 'Produk tidak ditemukan'
            ], 404);
        }

        // Kalau produk ditemukan
        return response()->json($produk, 200);
    }

    // public function diskusiProduk($id) {
    //     // $produk = Produk::with('diskusis')->find($id);
    //     $produk = Produk::find($id);
    //     if (!$produk) {
    //         return response()->json(['message' => 'Produk tidak ditemukan'], 404);
    //     }

    //     // Ambil hanya diskusi
    //     $diskusis = $produk->diskusis;

    //     return response()->json($diskusis);
    // }


}
