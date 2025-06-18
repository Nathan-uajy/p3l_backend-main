<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Penjadwalan;
use Carbon\Carbon;

class PenjadwalanController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaksiID' => 'required|exists:transaksis,transaksiID',
            'pegawaiID' => 'nullable|exists:pegawais,pegawaiID',
            'tipe' => 'required|in:pengiriman,pengambilan',
            'tanggal' => 'required|date',
            'waktu' => 'required|date_format:H:i',
        ]);

        // Validasi: tidak boleh pengiriman di hari yang sama jika pembelian lewat jam 16:00
        $tanggal = \Carbon\Carbon::parse($validated['tanggal']);
        $waktuSekarang = \Carbon\Carbon::now();

        if ($tanggal->isToday() && $waktuSekarang->hour >= 16 && $validated['tipe'] === 'pengiriman') {
            return response()->json([
                'message' => 'Pengiriman tidak bisa dijadwalkan hari ini karena sudah lewat pukul 16:00.'
            ], 422);
        }

        // Simpan ke penjadwalans
        $penjadwalan = \App\Models\Penjadwalan::create([
            'transaksiID' => $validated['transaksiID'],
            'pegawaiID' => $validated['pegawaiID'],
            'tipe' => $validated['tipe'],
            'status' => 'diproses', // status awal penjadwalan
            'tanggal' => $validated['tanggal'],
            'waktu' => $validated['waktu'],
        ]);

        // Update status transaksi (menjadi siap dikirim)
        $statusTransaksiBaru = $validated['tipe'] === 'pengiriman' ? 'siap dikirim' : 'siap diambil';

        \App\Models\Transaksi::where('transaksiID', $validated['transaksiID'])
            ->update(['status' => $statusTransaksiBaru]);

        return response()->json([
            'message' => 'Penjadwalan berhasil disimpan.',
            'penjadwalan' => $penjadwalan
        ]);
    }

    public function index()
    {
        $penjadwalans = \App\Models\Penjadwalan::with([
            'transaksi.pembeli',
            'transaksi.detailTransaksis.produk',
            'pegawai'
        ])
        ->orderBy('tanggal', 'desc')
        ->get();

        $result = $penjadwalans->map(function ($jadwal) {
            return [
                'penjadwalanID' => $jadwal->penjadwalanID,
                'tanggal' => $jadwal->tanggal,
                'waktu' => $jadwal->waktu,
                'tipe' => $jadwal->tipe,
                'status' => $jadwal->status,
                'namaKurir' => $jadwal->pegawai->nama ?? '-',
                'namaPembeli' => $jadwal->transaksi->pembeli->nama ?? '-',
                'alamat' => $jadwal->transaksi->pembeli->alamat ?? '-',
                'produk' => $jadwal->transaksi->detailTransaksis->map(function ($d) {
                    return $d->produk->namaProduk ?? 'Produk tidak diketahui';
                })

            ];
        });

        return response()->json($result);
    }

    public function updateStatus($id)
    {
        $penjadwalan = \App\Models\Penjadwalan::find($id);

        if (!$penjadwalan) {
            return response()->json(['message' => 'Penjadwalan tidak ditemukan'], 404);
        }

        // Cek apakah status saat ini valid untuk diubah
        if (!in_array($penjadwalan->status, ['diproses', 'siap dikirim'])) {
            return response()->json(['message' => 'Status tidak bisa diubah dari status saat ini.'], 400);
        }

        try {
            $penjadwalan->status = 'berhasil dikirim';
            $penjadwalan->save();

            return response()->json(['message' => 'Status berhasil diperbarui', 'penjadwalan' => $penjadwalan]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal update status',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function konfirmasiSelesai($id)
    {
        $penjadwalan = \App\Models\Penjadwalan::findOrFail($id);

        if ($penjadwalan->status !== 'dikirim') {
            return response()->json([
                'message' => 'Penjadwalan belum ditandai dikirim.'
            ], 400);
        }

        $penjadwalan->status = 'berhasil dikirim';
        $penjadwalan->save();

        return response()->json([
            'message' => 'Status penjadwalan berhasil dikonfirmasi sebagai berhasil dikirim.',
            'penjadwalan' => $penjadwalan
        ]);
    }

    public function konfirmasiDiterima($id)
    {
        $penjadwalan = Penjadwalan::findOrFail($id);

        if ($penjadwalan->status !== 'berhasil dikirim') {
            return response()->json(['message' => 'Penjadwalan belum berhasil dikirim.'], 400);
        }

        $penjadwalan->status = 'selesai';
        $penjadwalan->save();

        // Update status transaksi juga jika perlu
        $penjadwalan->transaksi->status = 'selesai';
        $penjadwalan->transaksi->save();

        return response()->json(['message' => 'Konfirmasi berhasil diterima.']);
    }


}
