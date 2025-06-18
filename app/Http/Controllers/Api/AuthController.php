<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;


class AuthController extends Controller
{


    public function getUser(Request $request)
    {
        $tipe = $request->header('tipe-akun');

        switch ($tipe) {
            case 'user':
                $user = auth('sanctum')->user();
                break;
            case 'pegawai':
                $user = \App\Models\Pegawai::where('id', auth()->id())->first();
                break;
            case 'penitip':
                $user = \App\Models\Penitip::where('id', auth()->id())->first();
                break;
            case 'organisasi':
                $user = \App\Models\Organisasi::where('id', auth()->id())->first();
                break;
            default:
                return response()->json(['message' => 'Tipe akun tidak dikenali'], 400);
        }

        return response()->json(['user' => $user]);
    }

    public function user(Request $request)
    {
        $tipe = $request->header('tipe-akun'); // ambil tipe akun dari header
        $user = $request->user(); // user dari sanctum (token)

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        return response()->json([
            'user' => $user,
            'tipe_akun' => $tipe,
        ]);
    }

public function register(Request $request)
{
    $registrationData = $request->all();

    $validate = Validator::make($registrationData, [
        'name' => 'required|max:60',
        'handle' => 'required|max:20|unique:users',
        'email' => 'required|email:rfc,dns|unique:users',
        'password' => 'required|min:8',
        'no_telp' => 'required|min:10',
    ]);

    if ($validate->fails()) {
        return response(['message' => $validate->errors()->first()], 400);
    }

    $registrationData['password'] = bcrypt($registrationData['password']);
    $user = User::create($registrationData);

    return response([
        'message' => 'Register Success',
        'user' => $user
    ], 200);
}


    public function login(Request $request)
    {
        $loginData = $request->all();

        $validate = Validator::make($loginData, [
            'email' => 'required|email:rfc,dns',
            'password' => 'required|min:8',
        ]);
        if ($validate->fails()) {
            return response(['message' => $validate->errors()->first()], 400);
        }

        if (!Auth::attempt($loginData)) {
            return response(['message' => 'Invalid email & password match'], 401);
        }
        $user = Auth::user();
        $token = $user->createToken('Authentication Token')->accessToken;

        return response([
            'message' => 'Authenticated',
            'user' => $user,
            'token_type' => 'Bearer',
            'access_token' => $token
        ]);
    }

    public function updateProfile(Request $request)
    {
        $tipe = $request->header('tipe-akun');
        $user = auth('sanctum')->user();

        // Validasi
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'no_telp' => 'nullable|string',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Update field umum
        $user->name = $request->name ?? $user->name;
        $user->email = $request->email ?? $user->email;
        $user->no_telp = $request->no_telp ?? $user->no_telp;

        // Handle upload foto
        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = time().'_'.$file->getClientOriginalName();
            $path = $file->storeAs('public/profile', $filename);
            $user->foto = 'storage/profile/' . $filename;
        }

        $user->save();

        return response()->json([
            'message' => 'Profil diperbarui.',
            'user' => $user
        ]);
    }


    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response([
            'message' => 'Logged out'
        ]);
    }
}
