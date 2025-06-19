<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Penitip;

class PenitipController extends Controller
{

    public function index()
    {
        return response()->json(Penitip::all());
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'email' => 'required|email|unique:penitips,email',
            'nik' => ['required', 'regex:/^[0-9]{16}$/', 'unique:penitips,nik'],
            'password' => 'required|min:8',
            'nomorHP' => 'required|min:10',
            'alamat' => 'required|string|max:255',
            'foto_ktp' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }

        $data = $request->only([
            'nama', 'email', 'nik', 'password', 'nomorHP', 'alamat'
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['role'] = 'penitip';

        // Upload foto KTP
        if ($request->hasFile('foto_ktp')) {
            $file = $request->file('foto_ktp');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/ktp', $filename);
            $data['foto_ktp'] = 'storage/ktp/' . $filename;
        }

        $penitip = Penitip::create($data);

        return response()->json([
        'message' => 'Penitip berhasil ditambahkan',
        'penitip' => [
            'id' => $penitip->penitipID,
            'name' => $penitip->nama,
            'email' => $penitip->email,
            'role' => $penitip->role,
            'foto_ktp' => $penitip->foto_ktp,
        ]
    ], 201);
    }

    public function destroy($id)
{
    $penitip = Penitip::find($id);
    if (!$penitip) {
        return response()->json(['message' => 'Penitip tidak ditemukan'], 404);
    }

    // Hapus foto KTP dari storage jika ada
    if ($penitip->foto_ktp && Storage::exists(str_replace('storage/', 'public/', $penitip->foto_ktp))) {
        Storage::delete(str_replace('storage/', 'public/', $penitip->foto_ktp));
    }

    $penitip->delete();

    return response()->json(['message' => 'Penitip berhasil dihapus']);
}
}
