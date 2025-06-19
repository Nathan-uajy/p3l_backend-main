<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alamat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AlamatController extends Controller
{

    public function index(Request $request)
{
    $user = $request->user();
    $query = Alamat::where('user_id', $user->id);

    if ($request->has('search')) {
        $search = $request->input('search');
        $query->where(function($q) use ($search) {
            $q->where('namaAlamat', 'LIKE', "%{$search}%")
                ->orWhere('namaPenerima', 'LIKE', "%{$search}%");
        });
    }

    return response()->json($query->get());
}

    public function store(Request $request)
    {
        $user = $request->user(); // Ambil user login melalui token

        // Validasi hanya data yang diinput lewat form
        $validated = $request->validate([
            'namaAlamat' => 'required|string|max:255',
            'alamat' => 'required|string',
            'kodePos' => 'nullable|string|max:10',
        ]);

        // Tambahkan otomatis nama & noHp dari user login
        $alamat = Alamat::create([
            'user_id'        => $user->id,
            'namaAlamat'     => $validated['namaAlamat'],
            'namaPenerima'   => $user->name,
            'noHpPenerima'   => $user->no_telp,
            'alamat'         => $validated['alamat'],
            'kodePos'        => $validated['kodePos'],
        ]);

        return response()->json([
            'message' => 'Alamat berhasil disimpan',
            'data' => $alamat,
        ], 201);
    }


    public function show($id)
    {
        $alamat = Alamat::findOrFail($id);
        return response()->json($alamat);
    }

    public function update(Request $request, $id)
    {
        $alamat = Alamat::findOrFail($id);

        $request->validate([
            'namaAlamat' => 'required|string|max:255',
            'alamat' => 'required|string|max:255',
            'kodePos' => 'nullable|string|max:20',
        ]);

        $alamat->namaAlamat = $request->namaAlamat;
        $alamat->alamat = $request->alamat;
        $alamat->kodePos = $request->kodePos;
        $alamat->save();

        return response()->json(['message' => 'Alamat berhasil diperbarui']);
    }

    public function destroy(Request $request, $id)
    {
        $alamat = Alamat::where('user_id', $request->user()->id)->findOrFail($id);
        $alamat->delete();

        return response()->json(['message' => 'Alamat berhasil dihapus']);
    }

}
