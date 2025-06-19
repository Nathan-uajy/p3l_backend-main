<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Merchandise;
use App\Models\Pembeli;

class MerchandiseController extends Controller
{

    public function index()
    {
        $data = \App\Models\Merchandise::select(
            'merchandiseID', 'nama', 'hargaMerch', 'stok', 'foto'
        )->get();

        // Tambahkan full URL ke foto
        foreach ($data as $item) {
            $item->foto = $item->foto ? asset('storage/' . $item->foto) : null;
        }

        return response()->json($data);
    }
    
    public function klaimMerchandise(Request $request)
    {
        $request->validate([
            'pembeliID' => 'required|exists:pembelis,pembeliID',
            'merchandiseID' => 'required|exists:merchandise,merchandiseID',
        ]);

        $pembeli = Pembeli::find($request->pembeliID);
        $merchandise = Merchandise::find($request->merchandiseID);

        // Cek stok
        if ($merchandise->stok <= 0) {
            return response()->json(['message' => '❌ Stok habis.'], 400);
        }

        // Cek poin cukup
        if ($pembeli->poinLoyalitas < $merchandise->hargaMerch) {
            return response()->json(['message' => '❌ Poin Anda tidak cukup.'], 400);
        }

        // Proses klaim
        $merchandise->stok -= 1;
        $merchandise->save();

        $pembeli->poinLoyalitas -= $merchandise->hargaMerch;
        $pembeli->save();

        return response()->json(['message' => '✅ Klaim berhasil!'], 200);
    }


}
