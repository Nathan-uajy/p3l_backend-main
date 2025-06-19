<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Pegawai;
use Illuminate\Support\Facades\Auth;

class PegawaiController extends Controller
{

    public function index()
    {
        $user = Auth::user();
        \Log::info('Current user:', ['role' => $user?->role, 'model' => get_class($user)]);
        return response()->json(Pegawai::all());
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'email' => 'required|email|unique:pegawais,email',
            'password' => 'required|min:8',
            'role' => 'required|string|max:50',
            'jabatan' => 'required|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }


        $data = $request->only([
            'nama', 'email', 'password', 'role', 'jabatan'
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
        ]
    ], 201);
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

    public function getKurirs()
    {
        $kurirs = \App\Models\Pegawai::where('jabatan', 'kurir')->get(['pegawaiID', 'nama', 'email']);
        return response()->json($kurirs);
    }

    public function getGudangs()
    {
        $gudangs = \App\Models\Pegawai::where('jabatan', 'gudang')->get(['pegawaiID', 'nama', 'email']);
        return response()->json($gudangs);
    }

}