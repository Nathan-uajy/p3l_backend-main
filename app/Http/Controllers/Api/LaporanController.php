<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Pdf;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Log;
use App\Models\Barang;
use App\Models\Penitip;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;



class LaporanController extends Controller
{
    public function laporanPerKategori()
{
    Log::info('🔍 Masuk ke laporanPerKategori');

    // Ambil produk yang sudah terjual
    try {
        $produkTerjual = \App\Models\Transaksi::where('status', 'selesai')
            ->with('detailTransaksis.produk')
            ->get()
            ->flatMap(function ($transaksi) {
                return $transaksi->detailTransaksis->map(function ($detail) {
                    return $detail->produk;
                });
            });

        Log::info('✅ Produk terjual berhasil diambil', ['jumlah' => $produkTerjual->count()]);
    } catch (\Exception $e) {
        Log::error('❌ Gagal ambil produk terjual', ['error' => $e->getMessage()]);
        return response()->json(['message' => 'Gagal ambil produk terjual'], 500);
    }

    try {
        $terjualPerKategori = $produkTerjual->groupBy('kategori')->map(function ($group) {
            return $group->count();
        });

        $produkBelumTerjual = \App\Models\Barang::where('status', 'aktif')->get();
        Log::info('✅ Produk belum terjual berhasil diambil', ['jumlah' => $produkBelumTerjual->count()]);

        $belumTerjualPerKategori = $produkBelumTerjual->groupBy('kategori')->map(function ($group) {
            return $group->count();
        });

        $kategoriGabungan = collect($terjualPerKategori)
            ->merge($belumTerjualPerKategori)
            ->keys()
            ->unique();

        $result = [];
        foreach ($kategoriGabungan as $kategori) {
            $result[$kategori] = [
                'jumlah_produk' => $terjualPerKategori[$kategori] ?? 0,
                'belum_terjual' => $belumTerjualPerKategori[$kategori] ?? 0,
            ];
        }

        Log::info('📦 Data kategori berhasil disusun', ['kategori' => $result]);

        return response()->json([
            'kategori' => $result
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Gagal proses laporan', ['error' => $e->getMessage()]);
        return response()->json(['message' => 'Gagal proses laporan'], 500);
    }
}

public function laporanPenitipanHabis()
{
    Log::info('📄 Masuk ke laporanPenitipanHabis');

    try {
        $barangs = Barang::where('status', 'masa penitipan habis')
            ->with('penitip') // pastikan relasi penitip() ada di model Barang
            ->get();

        $result = $barangs->map(function ($barang) {
            return [
                'kode_produk' => $barang->idProduk ?? '-', // pastikan kolom ini ada
                'nama_produk' => $barang->namaProduk,
                'penitip_id' => $barang->penitip->penitipID ?? '-',
                'nama_penitip' => $barang->penitip->nama ?? '-',
                'tgl_masuk' => Carbon::parse($barang->tglMulai)->format('d/m/Y'),
                'tgl_akhir' => Carbon::parse($barang->tglSelesai)->format('d/m/Y'),
                'batas_ambil' => Carbon::parse($barang->tglSelesai)->addDays(7)->format('d/m/Y'),
            ];
        });

        Log::info('✅ Data laporan berhasil diambil', ['jumlah' => count($result)]);

        return response()->json([
            'data' => $result
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Gagal ambil data laporan penitipan habis', ['error' => $e->getMessage()]);
        return response()->json(['message' => 'Gagal ambil data'], 500);
    }
}

public function laporanPerKategoriDiperpanjang(Request $request)
{
    try {
        // Ambil semua barang yang diperpanjang
        $barangs = DB::table('barangs')
            ->where('status', 'diperpanjang')
            ->get();

        // Ambil semua ID produk dari transaksi yang statusnya selesai
        $transaksiProdukIDs = DB::table('transaksis')
            ->where('status', 'selesai')
            ->pluck('idProduk') // GANTI INI DENGAN NAMA KOLOM YANG BENAR!
            ->toArray();

        // Filter hanya barang yang ID-nya pernah ditransaksikan
        $barangTerjual = $barangs->filter(function ($barang) use ($transaksiProdukIDs) {
            return in_array($barang->idProduk, $transaksiProdukIDs);
        });

        // Hitung per kategori
        $result = [];
        foreach ($barangTerjual as $item) {
            $kategori = $item->kategori ?? 'Tidak diketahui';
            $result[$kategori] = ($result[$kategori] ?? 0) + 1;
        }

        return response()->json([
            'kategori_diperpanjang' => $result
        ]);
    } catch (\Exception $e) {
        \Log::error('❌ Gagal ambil laporan barang diperpanjang', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
        ]);
        return response()->json(['message' => 'Gagal ambil data'], 500);
    }
}





}
