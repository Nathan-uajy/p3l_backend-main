<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    public function index()
    {
        $produk = Produk::all()->map(function ($item) {
            $item->gambar_url = $item->gambar
                ? asset('storage/produk/' . $item->gambar)
                : null;
            return $item;
        });

        return response()->json($produk, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'donasiID' => 'nullable|integer',
            'penitipID' => 'nullable|integer',
            'namaProduk' => 'required|string',
            'deskripsi' => 'nullable|string',
            'harga' => 'required|numeric',
            'kategori' => 'nullable|string',
            'status' => 'nullable|string',
            'tglMulai' => 'nullable|date',
            'tglSelesai' => 'nullable|date',
            'garansi' => 'nullable|date',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $gambar = null;
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/produk', $filename);
            $gambar = $filename;
        }

        $produk = Produk::create([
            'donasiID' => $request->donasiID,
            'penitipID' => $request->penitipID,
            'namaProduk' => $request->namaProduk,
            'deskripsi' => $request->deskripsi,
            'harga' => $request->harga,
            'kategori' => $request->kategori,
            'status' => $request->status,
            'tglMulai' => $request->tglMulai,
            'tglSelesai' => $request->tglSelesai,
            'garansi' => $request->garansi,
            'gambar' => $gambar,
        ]);

        return response()->json([
            'message' => 'Produk berhasil ditambahkan',
            'data' => $produk,
        ], 201);
    }
}