<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Pembeli;

class TransaksiController extends Controller
{
    public function index()
    {
        $transaksis = \App\Models\Transaksi::with('pembeli')
            ->where('status', 'diproses')
            ->where('tipe_transaksi', 'kirim')
            ->orderBy('waktu_transaksi', 'desc')
            ->get()
            ->map(function ($trx) {
                return [
                    'idTransaksi' => $trx->transaksiID,
                    'namaPembeli' => $trx->pembeli->nama ?? 'Tanpa Nama',
                    'tanggalPembelian' => date('Y-m-d', strtotime($trx->waktu_transaksi)),
                    'jamPembelian' => date('H:i', strtotime($trx->waktu_transaksi)),
                    'alamat' => $trx->pembeli->alamat ?? '-',
                ];
            });

        return response()->json($transaksis);
    }

    public function store(Request $request)
    {
        $transaksi = Transaksi::create($request->all());
        // Update totalHarga and poinEarned after creation
        $transaksi->totalHarga = $transaksi->getTotalHargaAttribute();
        $transaksi->poinEarned = $transaksi->getPoinEarnedAttribute();
        $transaksi->save();

        // Update pembeli's poinLoyalitas
        $pembeli = Pembeli::find($transaksi->pembeliID);
        if ($pembeli) {
            $pembeli->poinLoyalitas += $transaksi->poinEarned;
            $pembeli->save();
        }

        return response()->json($transaksi, 201);
    }

    public function show($id)
    {
        $transaksi = Transaksi::with(['pembeli', 'penitip', 'detailTransaksis.produk'])->findOrFail($id);
        return response()->json([
            'transaksiID' => $transaksi->transaksiID,
            'pembeli' => $transaksi->pembeli->nama ?? 'Tanpa Nama',
            'totalHarga' => $transaksi->totalHarga,
            'poinEarned' => $transaksi->poinEarned,
            'waktu_transaksi' => $transaksi->waktu_transaksi,
            'status' => $transaksi->status,
            'details' => $transaksi->detailTransaksis->map(function ($detail) {
                return [
                    'id_detail_transaksi' => $detail->id_detail_transaksi,
                    'produkID' => $detail->produkID,
                    'produkNama' => $detail->produk->namaProduk ?? 'Unknown',
                    'jumlah' => $detail->jumlah ?? 1,
                    'harga_satuan' => $detail->harga_satuan ?? 0,
                    'subtotal' => $detail->subtotal ?? 0,
                ];
            }),
        ]);
    }

    public function update(Request $request, $id)
    {
        $transaksi = Transaksi::findOrFail($id);
        $transaksi->update($request->all());
        $transaksi->totalHarga = $transaksi->getTotalHargaAttribute();
        $transaksi->poinEarned = $transaksi->getPoinEarnedAttribute();
        $transaksi->save();

        $pembeli = Pembeli::find($transaksi->pembeliID);
        if ($pembeli) {
            $pembeli->poinLoyalitas += $transaksi->poinEarned;
            $pembeli->save();
        }

        return response()->json($transaksi);
    }

    public function destroy($id)
    {
        Transaksi::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }

    public function buyerHistory(Request $request)
    {
        \Log::info('Buyer History Request', ['user_id' => $request->user()->pembeliID]);
        $pembeli = $request->user();
        $transaksis = Transaksi::with(['detailTransaksis.produk', 'pembeli'])
            ->where('pembeliID', $pembeli->pembeliID)
            ->orderBy('waktu_transaksi', 'desc')
            ->get()
            ->map(function ($trx) {
                return [
                    'transaksiID' => $trx->transaksiID,
                    'waktu_transaksi' => $trx->waktu_transaksi,
                    'status' => $trx->status ?? 'pending',
                    'totalHarga' => $trx->getTotalHargaAttribute(),
                    'poinEarned' => $trx->getPoinEarnedAttribute(),
                    'details' => $trx->detailTransaksis->map(function ($detail) {
                        return [
                            'id_detail_transaksi' => $detail->id_detail_transaksi,
                            'produkID' => $detail->produkID,
                            'produkNama' => $detail->produk->namaProduk ?? 'Unknown Product',
                            'jumlah' => $detail->jumlah ?? 1,
                            'harga_satuan' => $detail->harga_satuan ?? 0,
                            'subtotal' => $detail->getSubtotalAttribute(),
                        ];
                    })->toArray(),
                ];
            });

        \Log::info('Transaction History Response', ['count' => $transaksis->count()]);
        return response()->json($transaksis);
    }
}