<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Penjadwalan;
use Carbon\Carbon;
use App\Models\Transaksi;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class PenjadwalanController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaksiID' => 'required|exists:transaksis,transaksiID',
            'pegawaiID' => 'nullable|exists:pegawais,pegawaiID',
            'tanggal' => 'required|date',
            'waktu' => 'required|date_format:H:i',
        ]);

        // Ambil data transaksi asli
        $transaksi = Transaksi::with([
    'pembeli',
    'detailTransaksis.produk.penitip', // ✅ penting agar fcm_token bisa terbaca
    'penjadwalan.pegawai'
])->findOrFail($validated['transaksiID']);
        $tipe = $transaksi->tipe_transaksi === 'kirim' ? 'pengiriman' : 'pengambilan';

        // Cek apakah sudah dijadwalkan
        $sudahAda = Penjadwalan::where('transaksiID', $transaksi->transaksiID)
            ->where('tipe', $tipe)
            ->first();

        if ($sudahAda) {
            return response()->json([
                'message' => '⚠️ Transaksi ini sudah memiliki jadwal ' . $tipe
            ], 409);
        }

        // //Validasi khusus pengiriman sore hari
        // if (now()->isToday() && now()->hour >= 16 && $tipe === 'pengiriman') {
        //     return response()->json([
        //         'message' => '❌ Pengiriman tidak bisa dijadwalkan hari ini karena sudah lewat pukul 16:00.'
        //     ], 422);
        // }

        // Buat penjadwalan
        $penjadwalan = Penjadwalan::create([
            'transaksiID' => $transaksi->transaksiID,
            'pegawaiID' => $validated['pegawaiID'],
            'tipe' => $tipe,
            'status' => 'diproses',
            'tanggal' => $validated['tanggal'],
            'waktu' => $validated['waktu'],
        ]);

        // Update status transaksi
        $transaksi->status = $tipe === 'pengiriman' ? 'siap dikirim' : 'siap diambil';
        $transaksi->save();

// $factory = (new Factory)->withServiceAccount(storage_path('firebase/reusemart-5c1fc-d3b489b21ba7.json'));
// $messaging = $factory->createMessaging();

// ✅ Ambil token-token
// $pembeliToken = optional($transaksi->pembeli)->fcm_token;
// $detail = $transaksi->detailTransaksis->first();

// Log::info('🧩 Detail transaksi pertama:', [
//     'produkID' => $detail?->produk?->idProduk,
//     'penitipID' => $detail?->produk?->penitip?->penitipID,
//     'penitipToken' => $detail?->produk?->penitip?->fcm_token,
// ]);

// $pembeliToken = optional($transaksi->pembeli)->fcm_token;
// // $penitipToken = optional($detail?->produk?->penitip)->fcm_token;
// $penitipToken = $transaksi->detailTransaksis
//             ->map(fn($detail) => optional($detail->produk->penitip)->fcm_token)
//             ->filter() // Hapus token yang null atau kosong
//             ->unique() // Hapus token duplikat jika ada penitip yang sama
//             ->values() // Reset index array
//             ->all();  // Ubah menjadi array biasa
// $penjadwalan->load('pegawai');
// $kurirToken = optional($penjadwalan->pegawai)->fcm_token;
// Log::info('📥 Token yang akan digunakan:', [
//     'pembeli' => $pembeliToken,
//     'penitip' => $penitipToken,
//     'kurir' => $kurirToken,
// ]);

// 📨 Buat isi notifikasi
// $judul = $tipe === 'pengiriman' ? 'Pesanan Akan Dikirim' : 'Pesanan Siap Diambil';
// $pesan = $tipe === 'pengiriman'
//     ? 'Barang kamu akan dikirim sesuai jadwal yang telah ditentukan.'
//     : 'Silakan datang mengambil barangmu sesuai jadwal yang ditentukan.';

// 📤 Kirim ke pembeli
// if ($pembeliToken) {
//     try {
//         $messaging->send(
//             CloudMessage::withTarget('token', $pembeliToken)
//                 ->withNotification(Notification::create($judul, $pesan))
//                 ->withData(['tipe' => 'transaksi', 'id' => (string) $transaksi->transaksiID])
//         );
//         Log::info('✅ Notifikasi ke pembeli berhasil.');
//     } catch (\Throwable $e) {
//         Log::error('❌ Notifikasi ke pembeli gagal.', [
//             'message' => $e->getMessage(),
//             'trace' => $e->getTraceAsString(),
//         ]);
//     }
// }

// 📤 Kirim ke penitip
// if ($penitipToken) {
//     try {
//         $messaging->send(
//             CloudMessage::withTarget('token', $penitipToken)
//                 ->withNotification(Notification::create('Update Barang', 'Barang titipan Anda sudah dijadwalkan.'))
//         );
//         Log::info('✅ Notifikasi ke penitip berhasil.');
//     } catch (\Throwable $e) {
//         Log::error('❌ Notifikasi ke penitip gagal.', [
//             'message' => $e->getMessage(),
//             'trace' => $e->getTraceAsString(),
//         ]);
//     }
// }

// 📤 Kirim ke kurir (jika pengiriman)
if ($kurirToken && $tipe === 'pengiriman') {
    try {
        $messaging->send(
            CloudMessage::withTarget('token', $kurirToken)
                ->withNotification(Notification::create('Tugas Baru', 'Ada pengiriman baru yang dijadwalkan untuk Anda.'))
        );
        Log::info('✅ Notifikasi ke kurir berhasil.');
    } catch (\Throwable $e) {
        Log::error('❌ Notifikasi ke kurir gagal.', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
        return response()->json([
            'message' => "✅ Jadwal $tipe berhasil disimpan.",
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
                'transaksiID' => $jadwal->transaksiID,
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

        if ($penjadwalan->status !== 'diproses') {
            return response()->json(['message' => 'Status tidak valid untuk ditandai kirim/ambil.'], 400);
        }

        try {
            // Cek tipe
            if ($penjadwalan->tipe === 'pengiriman') {
                $penjadwalan->status = 'berhasil dikirim';
            } elseif ($penjadwalan->tipe === 'pengambilan') {
                $penjadwalan->status = 'berhasil diambil'; // bedakan agar logis
            }

            $penjadwalan->save();

            return response()->json(['message' => 'Status berhasil ditandai', 'penjadwalan' => $penjadwalan]);
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
        // Menggunakan findOrFail untuk mengambil data penjadwalan dan relasi transaksinya.
        $penjadwalan = \App\Models\Penjadwalan::with('transaksi.pembeli', 'transaksi.detailTransaksis.produk.penitip')->findOrFail($id);

        // Validasi: hanya lanjut jika status sebelumnya sudah "berhasil dikirim" atau "berhasil diambil"
        if (!in_array($penjadwalan->status, ['berhasil dikirim', 'berhasil diambil'])) {
            return response()->json(['message' => 'Penjadwalan belum ditandai berhasil sebelumnya.'], 400);
        }

        // Ubah status penjadwalan dan transaksi menjadi "selesai"
        $penjadwalan->status = 'selesai';
        $penjadwalan->save();

        // Pastikan relasi transaksi ada sebelum mencoba menyimpannya
        if ($penjadwalan->transaksi) {
            $penjadwalan->transaksi->status = 'selesai';
            $penjadwalan->transaksi->save();
        }

        try {
            $factory = (new Factory)->withServiceAccount(storage_path('firebase/reusemart-5c1fc-d3b489b21ba7.json'));
            $messaging = $factory->createMessaging();

            // Kirim notifikasi ke Pembeli
            $pembeliToken = optional($penjadwalan->transaksi->pembeli)->fcm_token;
            if ($pembeliToken) {
                $messaging->send(
                    CloudMessage::withTarget('token', $pembeliToken)
                        ->withNotification(Notification::create(
                            '✅ Transaksi Selesai', 
                            'Terima kasih telah berbelanja di ReUse Mart! Jangan lupa berikan ulasanmu.'
                        ))
                        ->withData(['tipe' => 'transaksi', 'id' => (string)$penjadwalan->transaksi->transaksiID])
                );
            }

            // Kirim notifikasi ke semua Penitip yang terlibat
            // PERBAIKAN: Nama variabel disamakan menjadi $penitipTokens (dengan 's')
            // $penitipToken = $penjadwalan->transaksi->detailTransaksis
            //     ->map(fn($detail) => optional($detail->produk->penitip)->fcm_token)
            //     ->filter()->unique()->values()->all();
            
            $penitipToken = optional($penjadwalan->transaksi->penitip)->fcm_token;
            if ($penitipToken) {
                $messaging->send(
                    CloudMessage::withTarget('token', $penitipToken)
                        ->withNotification(Notification::create(
                            '💰 Dana Segera Cair!', 
                            'Barang Anda telah sampai di tangan pembeli. Pendapatan akan segera diproses.'
                        ))
                        // ->withData(['tipe' => 'penjualan_selesai', 'id' => (string)$transaksi->transaksiID]);
                );
            }

            Log::info("Notifikasi 'Barang Sampai' untuk transaksi #{$penjadwalan->transaksi->transaksiID} berhasil diproses.");

        } catch (\Throwable $e) {
            // Jika notifikasi gagal, proses utama tetap dianggap berhasil
            // Kegagalan pengiriman notifikasi dicatat di log tanpa mengembalikan error ke user
            Log::error('Gagal mengirim notifikasi Barang Sampai: ' . $e->getMessage());
        }

        return response()->json([
            'message' => '✅ Transaksi selesai dikonfirmasi.',
            'penjadwalan' => $penjadwalan
        ]);
    }

    public function getTugas(Request $request)
    {
        // Mengambil data kurir yang sedang login melalui token Sanctum
        $kurir = $request->user();

        // Mengambil data penjadwalan dengan relasi yang dibutuhkan
        $penjadwalans = Penjadwalan::with([
            'transaksi.pembeli',
            'transaksi.detailTransaksis.produk'
        ])
        // ->where('pegawaiID', $kurir->pegawaiID) // <-- Filter paling penting!
        ->where('pegawaiID', $kurir->pegawaiID) 
        ->where('tipe', 'pengiriman') // Hanya menampilkan tugas pengiriman
        // ->where('status', 'selesai')
        ->orderBy('tanggal', 'desc')
        ->orderBy('waktu', 'desc')
        ->get();

        // Transformasi data agar sesuai dengan kebutuhan UI di Flutter
        $result = $penjadwalans->map(function ($jadwal) {
            return [
                'penjadwalanID' => $jadwal->penjadwalanID,
                'tanggal' => $jadwal->tanggal,
                'waktu' => date('H:i', strtotime($jadwal->waktu)),
                'tipe' => $jadwal->tipe,
                'status' => $jadwal->status,
                'namaPembeli' => $jadwal->transaksi->pembeli->nama ?? 'N/A',
                'alamat' => $jadwal->transaksi->pembeli->alamat ?? 'N/A',
                'transaksiID' => $jadwal->transaksiID,
                'produk' => $jadwal->transaksi->detailTransaksis->map(function ($d) {
                    return $d->produk->namaProduk ?? 'Produk Dihapus';
                })->implode(', '), // Menggabungkan nama produk menjadi satu string
            ];
        });

        return response()->json($result);
    }


}
