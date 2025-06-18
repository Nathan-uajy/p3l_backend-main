<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Organisasi;

class organisasiController extends Controller
{

    public function index()
    {
        return response()->json(organisasi::all());
    }
    
    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'nama' => 'required|string|max:100',
    //         'email' => 'required|email|unique:penitips,email',
    //         // 'nik' => ['required', 'regex:/^[0-9]{16}$/', 'unique:penitips,nik'],
    //         'password' => 'required|min:8',
    //         'role' => 'required|min:10',
    //         'jabatan' => 'required|string|max:255',
    //         // 'foto_ktp' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    //     ]);

    //     // if ($validator->fails()) {
    //     //     return response()->json(['message' => $validator->errors()->first()], 400);
    //     // }

    //     $data = $request->only([
    //         'nama', 'email', 'password', 'role', 'jabatan'
    //     ]);

    //     $data['password'] = Hash::make($data['password']);
    //     $data['role'] = 'organisasi';

    //     // Upload foto KTP
    //     // if ($request->hasFile('foto_ktp')) {
    //     //     $file = $request->file('foto_ktp');
    //     //     $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
    //     //     $file->storeAs('public/ktp', $filename);
    //     //     $data['foto_ktp'] = 'storage/ktp/' . $filename;
    //     // }

    //     $organisasi = organisasi::create($data);

    //     return response()->json([
    //     'message' => 'organisasi berhasil ditambahkan',
    //     'organisasi' => [
    //         'id' => $organisasi->organisasiID,
    //         'name' => $organisasi->nama,
    //         'email' => $organisasi->email,
    //         'role' => $organisasi->role,
    //         'jabatan' => $organisasi->jabatan,
    //     ]
    // ], 201);
    // }

    public function update(Request $request, $id)
    {
        $organisasi = Organisasi::find($id);
        if (!$organisasi) {
            return response()->json(['message' => 'Organisasi tidak ditemukan'], 404);
        }

        $rules = [
            'namaOrganisasi' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|unique:organisasis,email,' . $id . ',organisasiID',
            'kontak' => 'sometimes|required|min:10',
            'alamat' => 'sometimes|required|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }

        $data = $request->only(['namaOrganisasi', 'email', 'kontak', 'alamat']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // Upload foto KTP baru
        // if ($request->hasFile('foto_ktp')) {
        //     if ($penitip->foto_ktp && Storage::exists(str_replace('storage/', 'public/', $penitip->foto_ktp))) {
        //         Storage::delete(str_replace('storage/', 'public/', $penitip->foto_ktp));
        //     }

        //     $file = $request->file('foto_ktp');
        //     $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        //     $file->storeAs('public/ktp', $filename);
        //     $data['foto_ktp'] = 'storage/ktp/' . $filename;
        // }

        $organisasi->update($data);

        return response()->json(['message' => 'Data organisasi berhasil diperbarui', 'organisasi' => $organisasi]);
    }

    public function destroy($id)
{
    $organisasi = organisasi::find($id);
    if (!$organisasi) {
        return response()->json(['message' => 'organisasi tidak ditemukan'], 404);
    }

    // Hapus foto KTP dari storage jika ada
    // if ($penitip->foto_ktp && Storage::exists(str_replace('storage/', 'public/', $penitip->foto_ktp))) {
    //     Storage::delete(str_replace('storage/', 'public/', $penitip->foto_ktp));
    // }

    $organisasi->delete();

    return response()->json(['message' => 'organisasi berhasil dihapus']);
}
}
