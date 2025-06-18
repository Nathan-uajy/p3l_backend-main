<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pembeli;
use App\Models\Transaksi;

class PembeliController extends Controller
{
    public function profile(Request $request)
    {
        \Log::info('Profile endpoint hit', ['user' => $request->user()]);
        $pembeli = $request->user();
        return response()->json([
            'pembeliID' => $pembeli->pembeliID,
            'nama' => $pembeli->nama,
            'email' => $pembeli->email,
            'nomorHP' => $pembeli->nomorHP,
            'alamat' => $pembeli->alamat,
            'poinLoyalitas' => $pembeli->poinLoyalitas,
            'role' => 'pembeli',
        ]);
    }

    public function updateProfile(Request $request)
    {
        $pembeli = $request->user();
        $pembeli->update($request->only(['nama', 'email', 'nomorHP', 'alamat']));
        return response()->json($pembeli);
    }
}