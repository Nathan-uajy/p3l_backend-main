<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\Pembeli;
use App\Models\Pegawai;
use App\Models\PasswordReset;

class LupaResetPassController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Cek apakah email pembeli
        $pembeli = Pembeli::where('email', $request->email)->first();
        if ($pembeli) {
            $token = Str::random(60);
            PasswordReset::updateOrCreate(
                ['email' => $pembeli->email],
                ['token' => $token, 'created_at' => now()]
            );

            $resetLink = url("http://localhost:5173/user-forgot-password?token=$token&email=" . $pembeli->email);

            // Kirim email
            Mail::raw("Klik link berikut untuk reset password: $resetLink", function ($message) use ($pembeli) {
                $message->to($pembeli->email)->subject('Reset Password Pembeli');
            });

            return response()->json(['message' => 'Link reset telah dikirim ke email Anda.']);
        }

        // Cek apakah pegawai
        

        return response()->json(['message' => 'Email tidak ditemukan.'], 404);
    }

    public function resetPassword($id)
    {
        $pegawai = Pegawai::find($id);
        if (!$pegawai) {
            return response()->json(['message' => 'Pegawai tidak ditemukan'], 404);
        }

        $passwordPlaintext = str_replace('-', '', $pegawai->tanggalLahir); // Contoh: "20250516"
        $pegawai->password = Hash::make($passwordPlaintext);
        $pegawai->save();

        return response()->json(['message' => 'Password pegawai telah di-reset ke tanggal lahir.']);
    }

    public function gantiPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $reset = PasswordReset::where([
            'email' => $request->email,
            'token' => $request->token,
        ])->first();

        if (!$reset) {
            return response()->json(['message' => 'Token tidak valid'], 400);
        }

        $pembeli = Pembeli::where('email', $request->email)->first();
        if ($pembeli) {
            $pembeli->password = Hash::make($request->password);
            $pembeli->save();

            // Hapus token
            $reset->delete();

            return response()->json(['message' => 'Password berhasil direset']);
        }

        return response()->json(['message' => 'Pengguna tidak ditemukan'], 404);
    }
}