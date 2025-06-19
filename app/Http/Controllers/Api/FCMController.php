<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FCMController extends Controller
{
    public function simpanToken(Request $request)
{
    $request->validate([
        'fcm_token' => 'required|string',
    ]);

    $user = Auth::user();

    \Log::info('🔥 Simpan FCM dipanggil', [
        'user_id' => $user->getKey(),
        'tipe' => get_class($user),
        'token_dikirim' => $request->fcm_token,
    ]);

    if ($user instanceof \App\Models\Pembeli) {
        $user->fcm_token = $request->fcm_token;
        $user->save();
        return response()->json(['message' => 'Token FCM disimpan untuk pembeli']);
    }

    if ($user instanceof \App\Models\Penitip) {
        $user->fcm_token = $request->fcm_token;
        $user->save();
        return response()->json(['message' => 'Token FCM disimpan untuk penitip']);
    }

    if ($user instanceof \App\Models\Pegawai) {
        $user->fcm_token = $request->fcm_token;
        $user->save();
        return response()->json(['message' => 'Token FCM disimpan untuk pegawai']);
    }

    return response()->json(['message' => 'Role tidak dikenali'], 400);
}

// app/Http/Controllers/Api/FCMController.php

public function hapusToken(Request $request)
{
    $request->validate([
        'fcm_token' => 'required'
    ]);

    $user = auth()->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $user->update(['fcm_token' => null]);

    return response()->json(['message' => '✅ Token berhasil dihapus']);
}

}
