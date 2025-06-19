<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Komisi;
use App\Models\Pegawai;
use App\Models\Penjadwalan;

class HunterController extends Controller
{
    public function profile(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'pegawai' || $user->jabatan !== 'hunter') {
            return response()->json(['message' => '❌ Bukan akun hunter'], 403);
        }

        // Ambil total komisi dari transaksi yang dijadwalkan oleh hunter ini
        $totalKomisi = Komisi::whereHas('transaksi.penjadwalans', function ($q) use ($user) {
            $q->where('pegawaiID', $user->pegawaiID);
        })->sum('komisi_hunter');

        // Ambil daftar riwayat komisi
        $riwayatKomisi = Komisi::join('penjadwalans', 'komisis.transaksiID', '=', 'penjadwalans.transaksiID')
            ->where('penjadwalans.pegawaiID', $user->pegawaiID)
            ->select(
                'komisis.transaksiID',
                'komisis.komisi_hunter',
                'komisis.jumlahKomisi',
                'komisis.persentase',
                'komisis.tanggalKomisi'
            )
            ->orderByDesc('komisis.tanggalKomisi')
            ->get();

        return response()->json([
            'nama' => $user->nama,
            'email' => $user->email,
            'jabatan' => $user->jabatan,
            'totalKomisi' => $totalKomisi,
            'riwayatKomisi' => $riwayatKomisi,
        ]);
    }

    public function riwayatKomisi(Request $request)
{
    $pegawai = $request->user();

    if ($pegawai->role !== 'pegawai' || $pegawai->jabatan !== 'hunter') {
        return response()->json(['message' => '❌ Bukan akun hunter'], 403);
    }

    // Ambil semua komisi berdasarkan penjadwalan yang dikerjakan hunter ini
    $komisiList = Komisi::join('penjadwalans', 'komisis.transaksiID', '=', 'penjadwalans.transaksiID')
        ->where('penjadwalans.pegawaiID', $pegawai->pegawaiID)
        ->select(
            'komisis.transaksiID',
            'komisis.komisi_hunter',
            'komisis.jumlahKomisi',
            'komisis.persentase',
            'komisis.tanggalKomisi'
        )
        ->orderByDesc('komisis.tanggalKomisi')
        ->get();

    return response()->json($komisiList);
}

    
}
