<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pembeli;
use Illuminate\Http\Request;

class PembeliController extends Controller
{
    // GET /api/pembeli/me
    // public function me(Request $request)
    // {
    //     $pembeli = Pembeli::where('user_id', $request->user()->id)->first();

    //     if (!$pembeli) {
    //         return response()->json(['message' => 'Data pembeli tidak ditemukan'], 404);
    //     }

    //     return response()->json([
    //         'namaPenerima' => $pembeli->nama_pembeli,
    //         'noHpPenerima' => $pembeli->no_hp
    //     ]);
    // }

    // (Opsional) GET /api/pembeli
    public function index()
    {
        // $pembeli = Pembeli::all();
        $pembeli =  auth()->user();
        return response()->json($pembeli);
    }

    // (Opsional) GET /api/pembeli/{id}
    public function show($id)
    {
        $pembeli = Pembeli::find($id);
        if (!$pembeli) {
            return response()->json(['message' => 'Pembeli tidak ditemukan'], 404);
        }
        return response()->json($pembeli);
    }
}
