<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RequestDonasi;
use Illuminate\Support\Facades\Auth;

class RequestDonasiController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return RequestDonasi::where('organisasiID', $user->organisasiID)->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'namaReqDonasi' => 'required|string|max:255',
            'kategoriReqDonasi' => 'required|string|max:255',
            'donasiID' => 'nullable|numeric'
        ]);

        $validated['organisasiID'] = Auth::user()->organisasiID;

        return RequestDonasi::create($validated);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'namaReqDonasi' => 'required|string|max:255',
            'kategoriReqDonasi' => 'required|string|max:255',
        ]);

        $donasi = RequestDonasi::where('organisasiID', Auth::user()->organisasiID)
                                ->findOrFail($id);
        $donasi->update($validated);

        return response()->json(['message' => 'Berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $donasi = RequestDonasi::where('organisasiID', Auth::user()->organisasiID)
                                ->findOrFail($id);
        $donasi->delete();

        return response()->json(['message' => 'Berhasil dihapus']);
    }

}
