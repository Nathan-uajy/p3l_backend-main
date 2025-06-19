<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;

class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        // return Notification::where('user_id', $request->user_id)
        //     ->orderBy('created_at', 'desc')
        //     ->get();

        return response()->json(All())->get();
    }

    public function store(Request $request)
    {
        Notification::create([
            'user_id' => $request->user_id,
            'title' => $request->title,
            'message' => $request->message
        ]);

        return response()->json(['message' => 'Notification created'], 201);
    }

    public function markAsRead($id)
    {
        $notif = Notification::findOrFail($id);
        $notif->is_read = true;
        $notif->save();

        return response()->json(['message' => 'Marked as read']);
    }

    public function getForUser($userId)
    {
        // Validasi sederhana untuk memastikan ID adalah angka
        if (!is_numeric($userId)) {
            return response()->json(['message' => 'User ID tidak valid.'], 400);
        }

        // Ambil notifikasi milik user tersebut, urutkan dari yang paling baru
        $notifications = Notification::where('user_id', $userId)
                                     ->orderBy('created_at', 'desc')
                                     ->get();

        return response()->json($notifications);
    }
}
