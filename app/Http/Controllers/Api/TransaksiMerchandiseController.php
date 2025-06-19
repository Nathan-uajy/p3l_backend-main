<?php

namespace App\Http\Controllers\Api;

use App\Models\TransaksiMerchandise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller;
use Carbon\Carbon;

class TransaksiMerchandiseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = TransaksiMerchandise::with(['pembeli', 'merchandise']);
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->whereHas('pembeli', function($subQ) use ($search) {
                        $subQ->where('nama', 'like', "%$search%");
                    })
                    ->orWhereHas('merchandise', function($subQ) use ($search) {
                        $subQ->where('nama', 'like', "%$search%");
                    });
                });
            }
            
            $data = $query->orderBy('penukaranID', 'desc')->get();
            
            // Format tanggal agar konsisten
            $data->transform(function ($item) {
                if ($item->tanggal_claim) {
                    $item->tanggal_claim = Carbon::parse($item->tanggal_claim)->format('Y-m-d');
                }
                if ($item->tanggal_ambil) {
                    $item->tanggal_ambil = Carbon::parse($item->tanggal_ambil)->format('Y-m-d');
                }
                return $item;
            });
            
            Log::info('TransaksiMerchandise data retrieved', ['count' => $data->count()]);
            return response()->json($data)
                ->header('Access-Control-Allow-Origin', 'http://localhost:5174')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type');
        } catch (\Exception $e) {
            Log::error('Error fetching TransaksiMerchandise: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'pembeliID' => 'required|exists:pembelis,pembeliID',
                'merchandiseID' => 'required|exists:merchandise,merchandiseID',
                'jumlah_penukaran' => 'required|integer|min:1',
                'jumlah_poin' => 'required|integer|min:0',
                'tanggal_claim' => 'required|date',
            ]);

            // Parse tanggal dengan timezone yang benar
            $tanggalClaim = Carbon::createFromFormat('Y-m-d', $request->tanggal_claim)->format('Y-m-d');
            $tanggalAmbil = null;
            
            if ($request->tanggal_ambil) {
                $tanggalAmbil = Carbon::createFromFormat('Y-m-d', $request->tanggal_ambil)->format('Y-m-d');
            }

            $transaksi = TransaksiMerchandise::create([
                'pembeliID' => $request->pembeliID,
                'merchandiseID' => $request->merchandiseID,
                'jumlah_penukaran' => $request->jumlah_penukaran,
                'jumlah_poin' => $request->jumlah_poin,
                'tanggal_claim' => $tanggalClaim,
                'tanggal_ambil' => $tanggalAmbil,
            ]);

            return response()->json($transaksi->load(['pembeli', 'merchandise']), 201);
            
        } catch (\Exception $e) {
            Log::error('Error creating TransaksiMerchandise: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create transaction',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateTanggalAmbil(Request $request, $id)
    {
        try {
            $request->validate([
                'tanggal_ambil' => 'required|date',
            ]);

            $transaksi = TransaksiMerchandise::findOrFail($id);
            
            // Parse tanggal dengan benar untuk menghindari timezone issues
            $tanggalAmbil = Carbon::createFromFormat('Y-m-d', $request->tanggal_ambil);
            $transaksi->tanggal_ambil = $tanggalAmbil->format('Y-m-d');
            $transaksi->save();

            // Format response
            $transaksi = $transaksi->load(['pembeli', 'merchandise']);
            if ($transaksi->tanggal_claim) {
                $transaksi->tanggal_claim = Carbon::parse($transaksi->tanggal_claim)->format('Y-m-d');
            }
            if ($transaksi->tanggal_ambil) {
                $transaksi->tanggal_ambil = Carbon::parse($transaksi->tanggal_ambil)->format('Y-m-d');
            }

            return response()->json([
                'message' => 'Tanggal ambil berhasil diperbarui.',
                'data' => $transaksi
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating tanggal ambil: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update tanggal ambil',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $transaksi = TransaksiMerchandise::findOrFail($id);
            $transaksi->delete();

            return response()->json(['message' => 'Data transaksi berhasil dihapus.']);
            
        } catch (\Exception $e) {
            Log::error('Error deleting TransaksiMerchandise: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to delete transaction',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}