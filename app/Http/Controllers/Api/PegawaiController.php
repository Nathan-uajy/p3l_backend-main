<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Pegawai;

class PegawaiController extends Controller
{

    public function index()
    {
        return response()->json(Pegawai::all());
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'email' => 'required|email|unique:penitips,email',
            // 'nik' => ['required', 'regex:/^[0-9]{16}$/', 'unique:penitips,nik'],
            'password' => 'required|min:8',
            'role' => 'required|min:10',
            'jabatan' => 'required|string|max:255',
            'tanggalLahir' => 'required|date',
            // 'foto_ktp' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // if ($validator->fails()) {
        //     return response()->json(['message' => $validator->errors()->first()], 400);
        // }

        $data = $request->only([
            'nama', 'email', 'password', 'role', 'jabatan', 'tanggalLahir'
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['role'] = 'pegawai';

        // Upload foto KTP
        // if ($request->hasFile('foto_ktp')) {
        //     $file = $request->file('foto_ktp');
        //     $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        //     $file->storeAs('public/ktp', $filename);
        //     $data['foto_ktp'] = 'storage/ktp/' . $filename;
        // }

        $pegawai = Pegawai::create($data);

        return response()->json([
        'message' => 'Pegawai berhasil ditambahkan',
        'pegawai' => [
            'id' => $pegawai->pegawaiID,
            'name' => $pegawai->nama,
            'email' => $pegawai->email,
            'role' => $pegawai->role,
            'jabatan' => $pegawai->jabatan,
            'tanggalLahir' => $pegawai->tanggalLahir
        ]
    ], 201);
    }

    public function update(Request $request, $id)
    {
        $pegawai = Pegawai::find($id);
        if (!$pegawai) {
            return response()->json(['message' => 'Pegawai tidak ditemukan'], 404);
        }

        $rules = [
            'nama' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|unique:pegawais,email,' . $id . ',pegawaiID',
            'password' => 'nullable|min:8',
            'jabatan' => 'sometimes|required',
            'tanggalLahir' => 'sometimes|required|date',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }

        $data = $request->only(['nama', 'email', 'jabatan', 'tanggalLahir']);
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

        $pegawai->update($data);

        return response()->json(['message' => 'Data pegawai berhasil diperbarui', 'pegawai' => $pegawai]);
    }

    public function destroy($id)
{
    $pegawai = Pegawai::find($id);
    if (!$pegawai) {
        return response()->json(['message' => 'Pegawai tidak ditemukan'], 404);
    }

    // Hapus foto KTP dari storage jika ada
    // if ($penitip->foto_ktp && Storage::exists(str_replace('storage/', 'public/', $penitip->foto_ktp))) {
    //     Storage::delete(str_replace('storage/', 'public/', $penitip->foto_ktp));
    // }

    $pegawai->delete();

    return response()->json(['message' => 'Pegawai berhasil dihapus']);
}

    public function show($id)
    {
        $pegawai = Pegawai::find($id);
        if (!$pegawai) {
            return response()->json(['message' => 'Pembeli tidak ditemukan'], 404);
        }
        return response()->json($pegawai);
    }
}
