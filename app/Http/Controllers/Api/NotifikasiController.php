<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Models\Penitip;
use Illuminate\Support\Facades\Log;

class NotifikasiController extends Controller
{
    public function kirimNotifikasi(Request $request)
    {
         $factory = (new Factory)->withServiceAccount(storage_path('firebase/reusemart-5c1fc-d3b489b21ba7.json'));
    $messaging = $factory->createMessaging();

    // Ambil semua token dari database
    $pembeliToken = $request->pembeli_token;
    $penitipToken = $request->penitip_token;
    $kurirToken   = $request->kurir_token;

    $judul = $request->judul;
    $pesan = $request->pesan;
    $dataTambahan = [
        'tipe' => $request->tipe ?? '',
        'id'   => $request->id ?? ''
    ];

    $berhasil = [];

    foreach ([
        'pembeli' => $pembeliToken,
        'penitip' => $penitipToken,
        'kurir'   => $kurirToken
    ] as $peran => $token) {
        if ($token) {
            try {
                $messaging->send(
                    CloudMessage::withTarget('token', $token)
                        ->withNotification(Notification::create($judul, $pesan))
                        ->withData($dataTambahan)
                );
                \Log::info("✅ Notifikasi ke $peran berhasil.");
                $berhasil[] = $peran;
            } catch (\Throwable $e) {
                \Log::error("❌ Gagal kirim notifikasi ke $peran", ['error' => $e->getMessage()]);
            }
        } else {
            \Log::warning("⚠️ Token untuk $peran kosong, notifikasi dilewati.");
        }
    }

    return response()->json([
        'status' => 'OK',
        'berhasil_dikirim_ke' => $berhasil
    ]);
    }

    public function kirimNotifUji()
{
    // Ambil token penitip tertentu (misalnya penitip ID 35)
    $penitip = Penitip::find(35);
    $token = $penitip?->fcm_token;

    if (!$token) {
        return response()->json(['message' => '❌ Token tidak ditemukan'], 404);
    }

    // Setup Firebase
    $factory = (new Factory)->withServiceAccount(storage_path('firebase/reusemart-5c1fc-d3b489b21ba7.json'));
    $messaging = $factory->createMessaging();

    // Buat pesan
    $message = CloudMessage::withTarget('token', $token)
        ->withNotification(Notification::create(
            'Notifikasi Uji Coba 🚀',
            'Selamat! Push berhasil dikirim dari Laravel ke device.'
        ));

    // Kirim pesan
    try {
    $messaging->send($message);

    Log::info('✅ Notifikasi berhasil dikirim ke FCM');
    Log::info('🎯 Target token: ' . $token);
    Log::info('📨 Payload: ', $message->jsonSerialize()); // bisa dump struktur CloudMessage
} catch (\Throwable $e) {
    Log::error('❌ Gagal kirim notifikasi: ' . $e->getMessage());
    return response()->json([
        'message' => '❌ Gagal kirim notifikasi',
        'error' => $e->getMessage(),
    ], 500);
}

}
}
