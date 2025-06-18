<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\Pembeli;
use App\Models\Penitip;
use App\Models\Organisasi;

class MultiLoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        // Daftar semua model dan nama tipenya
        $tables = [
            'user' => User::class,
            'pegawai' => Pegawai::class,
            'pembeli' => Pembeli::class,
            'penitip' => Penitip::class,
            'organisasi' => Organisasi::class,
        ];

        foreach ($tables as $tipe_akun => $model) {
            $user = $model::where('email', $credentials['email'])->first();

            // $jabatan = null;
            if (!$user) continue;

            $jabatan = $tipe_akun === 'pegawai' ? $user->jabatan ?? null : null;

            $inputPassword = $credentials['password'];
            $storedPassword = $user->password;

            $isHashed = strlen($storedPassword) === 60 && str_starts_with($storedPassword, '$2y$');
            $passwordValid = false;
            // $passwordValid = $isHashed
            //     ? Hash::check($inputPassword, $storedPassword)
            //     : $inputPassword === $storedPassword;

            // $isMatch = Hash::check($inputPassword, $storedPassword) || $inputPassword === $storedPassword;

            // if ($tipe_akun == 'pegawai') {
            //     $jabatan = $user->jabatan; // Pastikan kolom jabatan ada di model Pegawai
            // }

            if ($isHashed) {
                $passwordValid = Hash::check($inputPassword, $storedPassword);
            } else {
                // Jika password belum di-hash dan cocok, hash dan simpan
                if ($inputPassword === $storedPassword) {
                    $passwordValid = true;
                    $user->password = Hash::make($inputPassword);
                    $user->save();
                }
            }

            // if ($user && Hash::check($credentials['password'], $user->password)) 
            // if ($user && $credentials['password'] === $user->password) {
            //     // Buat token Sanctum
            //     $token = $user->createToken('auth_token')->plainTextToken;

            //     return response()->json([
            //         'message' => 'Login berhasil',
            //         'user' => [
            //             'id' => $user->id,
            //             'email' => $user->email,
            //             'name' => $user->name ?? $user->nama ?? $user->namaOrganisasi ?? 'Pengguna',
            //             'role' => $jabatan ?? $user->role ?? $tipe_akun,
            //         ],
            //         'role' => $jabatan ?? $user->role ?? $tipe_akun,
            //         'jabatan' => $jabatan,
            //         'tipe_akun' => $tipe_akun,
            //         'token_type' => 'Bearer',
            //         'access_token' => $token,
            //     ]);
            
            if ($passwordValid) {
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil',
                'user' => [
                    'id' => $user->id ?? $user->organisasiID ?? $user->penitipID ?? null,
                    'email' => $user->email,
                    'name' => $user->name ?? $user->nama ?? $user->namaOrganisasi ?? 'Pengguna',
                    'role' => $jabatan ?? $user->role ?? $tipe_akun,
                ],
                'role' => $jabatan ?? $user->role ?? $tipe_akun,
                'jabatan' => $jabatan,
                'tipe_akun' => $tipe_akun,
                'token_type' => 'Bearer',
                'access_token' => $token,
            ]); 

            }
        }

        // Jika tidak ditemukan pada semua tabel
        return response()->json([
            'message' => 'Email atau password salah',
        ], 401);
    }

    public function register(Request $request)
    {
        $tipe = $request->input('tipe_akun'); // misal: 'pegawai', 'pembeli', dst

        // Validasi tipe akun
        if (!in_array($tipe, ['user', 'pegawai', 'pembeli', 'penitip', 'organisasi'])) {
            return response()->json(['message' => 'Tipe akun tidak valid'], 400);
        }

        // Validasi dasar
        $rules = [
            'email' => 'required|email|unique:' . $tipe . 's,email',
            'password' => 'required|min:8',
        ];

        // Tambahan aturan tergantung tipe akun
        switch ($tipe) {
            case 'user':
            case 'pegawai':
                $rules['nama'] = 'required|string|max:100';
                break;
            case 'pembeli':
            case 'penitip':
                $rules['nama'] = 'required|string|max:100';
                $rules['nomorHP'] = 'required|min:10';
                $rules['alamat'] = 'required|string|max:255';
                break;
            case 'organisasi':
                $rules['kontak'] = 'required|string|max:20';
                $rules['alamat'] = 'required|string|max:255';
                $rules['namaOrganisasi'] = 'required|string|max:100';
                break;
        }

        // Validasi input
        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }

        // Siapkan data yang diisi
        $data = $request->all();
        $data['password'] = bcrypt($data['password']);
        $data['role'] = $tipe;

        // Pilih model sesuai tipe
        $modelClass = match ($tipe) {
            'user' => \App\Models\User::class,
            'pegawai' => \App\Models\Pegawai::class,
            'pembeli' => \App\Models\Pembeli::class,
            'penitip' => \App\Models\Penitip::class,
            'organisasi' => \App\Models\Organisasi::class,
        };

        $user = $modelClass::create($data);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil',
            'user' => [
                'id' => $user->penitipID ?? $user->id,
                'name' => $user->nama ?? $user->name,
                'handle' => $user->handle ?? '',
                'email' => $user->email,
                'role' => $tipe,
            ],
            'role' => $tipe,
            'token_type' => 'Bearer',
            'access_token' => $token,
        ]);


        
    }
}
