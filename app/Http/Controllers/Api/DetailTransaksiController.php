<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetailTransaksi;
use Illuminate\Http\Request;

class DetailTransaksiController extends Controller
{
    public function index()
    {
        return response()->json(DetailTransaksi::with(['produk', 'transaksi'])->get());
    }

    public function store(Request $request)
    {
        $detail = DetailTransaksi::create($request->all());
        return response()->json($detail, 201);
    }

    public function show($id)
    {
        return response()->json(DetailTransaksi::with(['produk', 'transaksi'])->findOrFail($id));
    }

    public function destroy($id)
    {
        DetailTransaksi::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
}
