<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Merchandise;
use App\Models\Pembeli;
use App\Models\TransaksiMerchandise;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MerchandiseController extends Controller
{
    public function index()
    {
        try {
            $data = Merchandise::select('merchandiseID', 'nama', 'jumlahPoin', 'stock', 'foto')->get();

            foreach ($data as $item) {
                $item->foto = $item->foto ? asset('storage/merch/' . $item->foto) : null;
            }

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error fetching merchandise: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Gagal mengambil data merchandise'], 500);
        }
    }
    
    public function klaimMerchandise(Request $request)
    {
        try {
            Log::info('Klaim merchandise request:', [
                'body' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            $validatedData = $request->validate([
                'pembeliID' => 'required|integer|exists:pembelis,pembeliID',
                'merchandiseID' => 'required|integer|exists:merchandise,merchandiseID',
            ]);

            Log::info('Validated data:', $validatedData);

            $pembeli = Pembeli::find($validatedData['pembeliID']);
            if (!$pembeli) {
                Log::warning('Pembeli not found:', ['pembeliID' => $validatedData['pembeliID']]);
                return response()->json(['message' => '❌ Data pembeli tidak ditemukan.'], 404);
            }

            $merchandise = Merchandise::find($validatedData['merchandiseID']);
            if (!$merchandise) {
                Log::warning('Merchandise not found:', ['merchandiseID' => $validatedData['merchandiseID']]);
                return response()->json(['message' => '❌ Merchandise tidak ditemukan.'], 404);
            }

            Log::info('Found data:', [
                'pembeli' => $pembeli->toArray(),
                'merchandise' => $merchandise->toArray()
            ]);

            if ($merchandise->stock <= 0) {
                return response()->json(['message' => '❌ Stok habis.'], 400);
            }

            if ($pembeli->poinLoyalitas < $merchandise->jumlahPoin) {
                return response()->json([
                    'message' => '❌ Poin Anda tidak cukup.',
                    'required_points' => $merchandise->jumlahPoin,
                    'current_points' => $pembeli->poinLoyalitas
                ], 400);
            }

            DB::beginTransaction();

            try {
                $merchandise->stock -= 1;
                $merchandise->save();

                $pembeli->poinLoyalitas -= $merchandise->jumlahPoin;
                $pembeli->save();

                TransaksiMerchandise::create([
                    'pembeliID' => $pembeli->pembeliID,        // Gunakan pembeliID sebagai foreign key
                    'merchandiseID' => $merchandise->merchandiseID,
                    'jumlah_penukaran' => 1,
                    'jumlah_poin' => $merchandise->jumlahPoin,
                    'tanggal_claim' => now()->toDateString(),
                    'tanggal_ambil' => null,
                    // Hapus 'nama_pembeli' karena sudah tidak ada
                ]);

                DB::commit();

                Log::info('Klaim merchandise successful:', [
                    'pembeliID' => $pembeli->pembeliID,
                    'merchandiseID' => $merchandise->merchandiseID,
                    'points_used' => $merchandise->jumlahPoin
                ]);

                return response()->json([
                    'message' => '✅ Klaim berhasil!',
                    'remaining_points' => $pembeli->poinLoyalitas,
                    'remaining_stock' => $merchandise->stock
                ], 200);

            } catch (\Exception $e) {
                DB::rollback();
                Log::error('Error during transaction:', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return response()->json([
                    'message' => '❌ Terjadi kesalahan saat memproses transaksi.',
                    'error' => $e->getMessage()
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed:', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            return response()->json([
                'message' => '❌ Data tidak valid.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error in klaimMerchandise:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);
            return response()->json([
                'message' => '❌ Terjadi kesalahan saat memproses klaim.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}