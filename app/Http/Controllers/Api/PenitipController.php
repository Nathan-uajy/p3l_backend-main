<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Penitip;
use App\Models\Barang;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PenitipController extends Controller
{
    public function index()
    {
        $penitips = Penitip::select('penitipID', 'nama', 'email')->get();
        return response()->json($penitips);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'email' => 'required|email|unique:penitips,email',
            'nik' => ['required', 'regex:/^[0-9]{16}$/', 'unique:penitips,nik'],
            'password' => 'required|min:8',
            'nomorHP' => 'required|min:10',
            'alamat' => 'required|string|max:255',
            'foto_ktp' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }

        $data = $request->only(['nama', 'email', 'nik', 'password', 'nomorHP', 'alamat']);
        $data['password'] = Hash::make($data['password']);
        $data['role'] = 'penitip';

        if ($request->hasFile('foto_ktp')) {
            $file = $request->file('foto_ktp');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/ktp', $filename);
            $data['foto_ktp'] = 'storage/ktp/' . $filename;
        }

        $penitip = Penitip::create($data);
        $token = $penitip->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Penitip berhasil ditambahkan dan langsung login',
            'penitip' => [
                'id' => $penitip->penitipID,
                'name' => $penitip->nama,
                'email' => $penitip->email,
                'role' => $penitip->role,
                'foto_ktp' => $penitip->foto_ktp,
            ],
            'role' => 'penitip',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $penitip = Penitip::find($id);
        if (!$penitip) {
            return response()->json(['message' => 'Penitip tidak ditemukan'], 404);
        }

        $rules = [
            'nama' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|unique:penitips,email,' . $id . ',penitipID',
            'nik' => ['sometimes', 'required', 'regex:/^[0-9]{16}$/', 'unique:penitips,nik,' . $id . ',penitipID'],
            'password' => 'nullable|min:8',
            'nomorHP' => 'sometimes|required|min:10',
            'alamat' => 'sometimes|required|string|max:255',
            'foto_ktp' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }

        $data = $request->only(['nama', 'email', 'nik', 'nomorHP', 'alamat']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('foto_ktp')) {
            if ($penitip->foto_ktp && Storage::exists(str_replace('storage/', 'public/', $penitip->foto_ktp))) {
                Storage::delete(str_replace('storage/', 'public/', $penitip->foto_ktp));
            }
            $file = $request->file('foto_ktp');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/ktp', $filename);
            $data['foto_ktp'] = 'storage/ktp/' . $filename;
        }

        $penitip->update($data);
        return response()->json(['message' => 'Data penitip berhasil diperbarui', 'penitip' => $penitip]);
    }

    public function destroy($id)
    {
        $penitip = Penitip::find($id);
        if (!$penitip) {
            return response()->json(['message' => 'Penitip tidak ditemukan'], 404);
        }

        if ($penitip->foto_ktp && Storage::exists(str_replace('storage/', 'public/', $penitip->foto_ktp))) {
            Storage::delete(str_replace('storage/', 'public/', $penitip->foto_ktp));
        }

        $penitip->delete();
        return response()->json(['message' => 'Penitip berhasil dihapus']);
    }

    public function search(Request $request)
    {
        $keyword = $request->input('q');
        $results = Penitip::where('nama', 'like', "%$keyword%")
            ->orWhere('email', 'like', "%$keyword%")
            ->orWhere('nik', 'like', "%$keyword%")
            ->orWhere('nomorHP', 'like', "%$keyword%")
            ->orWhere('alamat', 'like', "%$keyword%")
            ->get();
        return response()->json($results);
    }

    public function profile(Request $request)
    {
        $penitip = $request->user();
        if (!$penitip) {
            return response()->json(['message' => 'Pengguna tidak ditemukan'], 404);
        }
        return response()->json([
            'penitipID' => $penitip->penitipID,
            'nama' => $penitip->nama,
            'email' => $penitip->email,
            'saldo' => $penitip->saldo ?? 0.00,
            'poinLoyalitas' => $penitip->poinLoyalitas ?? 0,
            'role' => $penitip->role,
            'isTopSeller' => $penitip->isTopSeller,
            'topSellerUntil' => $penitip->topSellerUntil,
        ]);
    }

    public function listWithSales(Request $request)
    {
        $penitips = Penitip::withCount(['barangs as totalItemsDeposited'])
            ->get()
            ->map(function ($penitip) {
                $totalSales = Barang::where('penitipID', $penitip->penitipID)
                    ->where('status', 'terjual')
                    ->count();
                $lastSaleDate = Barang::where('penitipID', $penitip->penitipID)
                    ->where('status', 'terjual')
                    ->latest('updated_at')
                    ->value('updated_at');
                return [
                    'penitipID' => $penitip->penitipID,
                    'nama' => $penitip->nama,
                    'totalSales' => $totalSales,
                    'totalItemsDeposited' => $penitip->totalItemsDeposited,
                    'lastSaleDate' => $lastSaleDate ? $lastSaleDate->toDateString() : null,
                    'isTopSeller' => $penitip->isTopSeller,
                    'topSellerUntil' => $penitip->topSellerUntil,
                ];
            });
        return response()->json($penitips);
    }

    public function setTopSeller($penitipID)
{
    $penitip = Penitip::find($penitipID);
    if (!$penitip) {
        return response()->json(['message' => 'Penitip tidak ditemukan'], 404);
    }

    Log::info('Set Top Seller untuk penitipID: ' . $penitipID . ', saldo awal: ' . $penitip->saldo);

    $currentDate = Carbon::now();

    // Debugging: Ambil detail penjualan tanpa filter bulan
    $sales = Barang::where('penitipID', $penitipID)
        ->where('status', 'terjual')
        ->get();
    foreach ($sales as $sale) {
        Log::info('Penjualan: namaProduk=' . $sale->namaProduk . ', harga=' . $sale->harga . ', updated_at=' . $sale->updated_at);
    }

    $totalSales = Barang::where('penitipID', $penitipID)
        ->where('status', 'terjual')
        ->count();
    $totalSalesAmount = Barang::where('penitipID', $penitipID)
        ->where('status', 'terjual')
        ->sum('harga');
    Log::info('Total Sales: ' . $totalSales . ', Total Sales Amount: ' . $totalSalesAmount);

    $bonusAmount = round($totalSalesAmount * 0.01, 2); // 1% dari total penjualan
    if ($bonusAmount > 0) {
        $loyaltyPoints = floor($bonusAmount / 10000); // Konversi bonus ke poin (1 poin = Rp10.000)
        $penitip->poinLoyalitas += $loyaltyPoints;
        Log::info('Bonus Amount: ' . $bonusAmount . ', Poin ditambahkan: ' . $loyaltyPoints . ', Saldo baru: ' . $penitip->poinLoyalitas);
    } else {
        Log::warning('Tidak ada bonus karena totalSalesAmount adalah 0');
    }

    $penitip->isTopSeller = true;
    $penitip->topSellerUntil = $currentDate->endOfMonth();
    
    $saved = $penitip->save();
    if ($saved) {
        Log::info('Penyimpanan berhasil, Saldo akhir: ' . $penitip->poinLoyalitas);
    } else {
        Log::error('Penyimpanan gagal untuk penitipID: ' . $penitipID);
    }

    return response()->json([
        'message' => 'Top Seller ditetapkan, bonus ditambahkan ke saldo',
        'saldo' => $penitip->poinLoyalitas,
        'bonusAmount' => $bonusAmount,
        'loyaltyPoints' => isset($loyaltyPoints) ? $loyaltyPoints : 0,
        'saved' => $saved,
    ]);
}

    public function getBarangDititipkan($penitipID)
    {
        $penitip = Penitip::find($penitipID);
        if (!$penitip) {
            return response()->json(['message' => 'Penitip tidak ditemukan'], 404);
        }

        $barangs = $penitip->barangs()
            ->select('idProduk', 'namaProduk', 'status', 'tglMulai', 'tglSelesai', 'harga', 'gambar as gambar_url', 'gambar2 as gambar2_url')
            ->get();
        return response()->json($barangs);
    }
}