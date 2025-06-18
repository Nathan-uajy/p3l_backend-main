<?php

namespace App\Http\Controllers\Api;

use App\Models;
use App\Models\RequestDonasi;
use App\Models\DetailTransaksi;
use App\Models\Penitip;
use App\Models\Donasi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
// use Illuminate\Support\Facades\Auth;

class LaporanController extends controller 
{
    public function laporanDonasiBarang(Request $request)
    {
        // Contoh query, sesuaikan dengan struktur database Anda
        $donasiData = Donasi::with(['produk.penitip', 'organisasi'])
            ->whereYear('tanggal_donasi', $request->input('tahun', date('Y')))
            ->get()
            ->map(function($donasi) {
                return [
                    'kode_produk' => optional($donasi->produk)->idProduk,
                    'nama_produk' => optional($donasi->produk)->namaProduk,
                    'id_penitip' => optional($donasi->produk->penitip)->penitipID,
                    'nama_penitip' => optional($donasi->produk->penitip)->nama,
                    'tanggal_donasi'  => $donasi->tanggal_donasi ? Carbon::parse($donasi->tanggal_donasi)->format('d/m/Y') : '-',
                    'organisasi'      => optional($donasi->organisasi)->namaOrganisasi,
                    'nama_penerima'   => $donasi->namaPenerima,
                ];
            });

        return response()->json($donasiData);
    }

    // Tambahkan method ini di dalam LaporanController.php

    public function laporanRequestDonasi(Request $request)
    {
        // Contoh query, asumsikan Anda punya model RequestDonasi
        // dan ada kolom 'status' untuk menandai apakah sudah terpenuhi.
        $requestData = RequestDonasi::with('organisasi')
            // ->where('status', 'belum_terpenuhi') // Filter hanya yang belum terpenuhi
            ->where('donasiID', NULL)
            ->get()
            ->map(function($req) {
                return [
                    'id_organisasi' => optional($req->organisasi)->organisasiID,
                    'nama_organisasi' => optional($req->organisasi)->namaOrganisasi,
                    'alamat_organisasi' => optional($req->organisasi)->alamat,
                    'request_barang' => $req->namaReqDonasi, // Kolom request dari tabel request_donasi
                ];
            });

        return response()->json($requestData);
    }

    public function laporanTransaksiPenitip(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'penitip_id' => 'required|integer|exists:penitips,penitipID',
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000|max:' . date('Y'),
        ]);

        $penitipId = $validated['penitip_id'];
        $bulan = $validated['bulan'];
        $tahun = $validated['tahun'];

        // Query untuk mengambil data produk terjual dari penitip tertentu
        $laporanData = DetailTransaksi::with(['produk', 'transaksi'])
            ->whereHas('produk', function ($query) use ($penitipId) {
                $query->where('penitipID', $penitipId);
            })
            ->whereHas('transaksi', function ($query) use ($bulan, $tahun) {
                $query->where('status', 'selesai')
                      ->whereMonth('waktu_transaksi', $bulan)
                      ->whereYear('waktu_transaksi', $tahun);
            })
            ->get()
            ->map(function ($detail) {
                // Asumsi dan placeholder untuk logika bisnis
                $hargaJual = optional($detail->produk)->harga;
                $komisi = $hargaJual * 0.20; // Contoh komisi 20%
                $hargaJualBersih = $hargaJual - $komisi;

                // Contoh logika bonus: jika terjual dalam 7 hari
                $tanggalMasuk = Carbon::parse(optional($detail->produk)->created_ad);
                $tanggalLaku = Carbon::parse(optional($detail->transaksi)->waktu_transaksi);
                $bonusCepat = $tanggalLaku->diffInDays($tanggalMasuk) < 7 ? $hargaJualBersih * 0.10 : 0; // Bonus 10%

                $pendapatan = $hargaJualBersih + $bonusCepat;

                return [
                    'kode_produk' => optional($detail->produk)->idProduk,
                    'nama_produk' => optional($detail->produk)->namaProduk,
                    'tanggal_masuk' => optional($detail->produk)->created_at ? Carbon::parse(optional($detail->produk)->created_ad)->format('d/m/Y') : '-',
                    'tanggal_laku' => optional($detail->transaksi)->waktu_transaksi ? $tanggalLaku->format('d/m/Y') : '-',
                    'harga_jual_bersih' => $hargaJualBersih,
                    'bonus_terjual_cepat' => $bonusCepat,
                    'pendapatan' => $pendapatan,
                ];
            });

        // Ambil data penitip untuk header laporan
        $penitip = Penitip::find($penitipId);

        return response()->json([
            'penitip' => [
                'id' => $penitip->penitipID,
                'nama' => $penitip->nama,
            ],
            'laporan' => $laporanData,
        ]);
    }
}