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

        $data = $request->only(['nama', 'email', 'nik', 'password', 'nomorHP', 'alamat']);
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

        // Langsung buat token login untuk penitip
        $token = $penitip->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Penitip berhasil ditambahkan dan langsung login',
            'penitip' => [
                'id' => $penitip->penitipID,
                'name' => $penitip->nama,
                'email' => $penitip->email,
                'role' => $penitip->role,
                'foto_ktp' => $penitip->foto_ktp,
            ],
            'role' => 'penitip',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $penitip = Penitip::find($id);
        if (!$penitip) {
            return response()->json(['message' => 'Penitip tidak ditemukan'], 404);
        }

        $rules = [
            'nama' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|unique:penitips,email,' . $id . ',penitipID',
            'nik' => ['sometimes', 'required', 'regex:/^[0-9]{16}$/', 'unique:penitips,nik,' . $id . ',penitipID'],
            'password' => 'nullable|min:8',
            'nomorHP' => 'sometimes|required|min:10',
            'alamat' => 'sometimes|required|string|max:255',
            'foto_ktp' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }

        $data = $request->only(['nama', 'email', 'nik', 'nomorHP', 'alamat']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // Upload foto KTP baru
        if ($request->hasFile('foto_ktp')) {
            if ($penitip->foto_ktp && Storage::exists(str_replace('storage/', 'public/', $penitip->foto_ktp))) {
                Storage::delete(str_replace('storage/', 'public/', $penitip->foto_ktp));
            }

            $file = $request->file('foto_ktp');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/ktp', $filename);
            $data['foto_ktp'] = 'storage/ktp/' . $filename;
        }

        $penitip->update($data);

        return response()->json(['message' => 'Data penitip berhasil diperbarui', 'penitip' => $penitip]);
    }

    public function destroy($id)
    {
        $penitip = Penitip::find($id);
        if (!$penitip) {
            return response()->json(['message' => 'Penitip tidak ditemukan'], 404);
        }

        if ($penitip->foto_ktp && Storage::exists(str_replace('storage/', 'public/', $penitip->foto_ktp))) {
            Storage::delete(str_replace('storage/', 'public/', $penitip->foto_ktp));
        }

        $penitip->delete();

        return response()->json(['message' => 'Penitip berhasil dihapus']);
    }

    public function search(Request $request)
    {
        $keyword = $request->input('q');

        $results = Penitip::where('nama', 'like', "%$keyword%")
            ->orWhere('email', 'like', "%$keyword%")
            ->orWhere('nik', 'like', "%$keyword%")
            ->orWhere('nomorHP', 'like', "%$keyword%")
            ->orWhere('alamat', 'like', "%$keyword%")
            ->get();

        return response()->json($results);
    }

    public function penitipSaldoBesar()
{
    $penitip = \App\Models\Penitip::where('saldo', '>=', 500000)->get();

    return response()->json($penitip);
}

}
