<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Barang;
use App\Models\Penitip; // Pastikan ini ada
use Carbon\Carbon;

class BarangController extends Controller
{
    public function getByPenitip($penitipID)
    {
        $barangs = Barang::where('penitipID', $penitipID)
            ->with(['penitip', 'kategori'])
            ->get()
            ->map(function ($b) {
                return [
                    'idProduk'     => $b->idProduk,
                    'donasiID'     => $b->donasiID,
                    'penitipID'    => $b->penitipID,
                    'namaProduk'   => $b->namaProduk,
                    'deskripsi'    => $b->deskripsi,
                    'harga'        => $b->harga,
                    'kategoriID'   => $b->kategoriID,
                    'namaKategori' => $b->kategori ? $b->kategori->namaKategori : null,
                    'status'       => $b->status,
                    'garansi'      => $b->garansi,
                    'tglMulai'     => $b->tglMulai,
                    'tglSelesai'   => $b->tglSelesai,
                    'gambar_url'   => $b->gambar ? url('storage/' . $b->gambar) : null,
                    'gambar2_url'  => $b->gambar2 ? url('storage/' . $b->gambar2) : null,
                    'created_at'   => $b->created_at,
                    'updated_at'   => $b->updated_at,
                ];
            });

        return response()->json($barangs);
    }

    public function show($id)
    {
        $barang = Barang::with(['penitip', 'kategori'])->find($id);
        if (!$barang) {
            return response()->json(['message' => 'Barang tidak ditemukan'], 404);
        }

        return response()->json([
            'idProduk'     => $barang->idProduk,
            'donasiID'     => $barang->donasiID,
            'penitipID'    => $barang->penitipID,
            'namaProduk'   => $barang->namaProduk,
            'deskripsi'    => $barang->deskripsi,
            'harga'        => $b->harga,
            'kategoriID'   => $b->kategoriID,
            'namaKategori' => $b->kategori ? $b->kategori->namaKategori : null,
            'status'       => $b->status,
            'garansi'      => $b->garansi,
            'tglMulai'     => $b->tglMulai,
            'tglSelesai'   => $b->tglSelesai,
            'gambar_url'   => $b->gambar ? url('storage/' . $b->gambar) : null,
            'gambar2_url'  => $b->gambar2 ? url('storage/' . $b->gambar2) : null,
            'created_at'   => $b->created_at,
            'updated_at'   => $b->updated_at,
        ]);
    }

    public function search(Request $request)
    {
        $q = $request->query('q');
        $penitipID = $request->query('penitipID');

        if (empty($q) || empty($penitipID)) {
            return response()->json(['message' => 'Parameter q dan penitipID diperlukan'], 400);
        }

        $query = Barang::where('penitipID', $penitipID)
            ->with(['kategori'])
            ->where(function ($query) use ($q) {
                $query->where('namaProduk', 'like', "%$q%")
                    ->orWhere('status', 'like', "%$q%")
                    ->orWhere('garansi', 'like', "%$q%")
                    ->orWhereHas('kategori', function ($kategoriQuery) use ($q) {
                        $kategoriQuery->where('namaKategori', 'like', "%$q%");
                    });
            });

        $barangs = $query->get()->map(function ($b) {
            return [
                'idProduk'     => $b->idProduk,
                'donasiID'     => $b->donasiID,
                'penitipID'    => $b->penitipID,
                'namaProduk'   => $b->namaProduk,
                'deskripsi'    => $b->deskripsi,
                'harga'        => $b->harga,
                'kategoriID'   => $b->kategoriID,
                'namaKategori' => $b->kategori ? $b->kategori->namaKategori : null,
                'status'       => $b->status,
                'garansi'      => $b->garansi,
                'tglMulai'     => $b->tglMulai,
                'tglSelesai'   => $b->tglSelesai,
                'gambar_url'   => $b->gambar ? url('storage/' . $b->gambar) : null,
                'gambar2_url'  => $b->gambar2 ? url('storage/' . $b->gambar2) : null,
                'created_at'   => $b->created_at,
                'updated_at'   => $b->updated_at,
            ];
        });

        return response()->json($barangs);
    }

    public function perpanjang($id)
    {
        $barang = Barang::find($id);

        if (!$barang) {
            return response()->json(['message' => 'Barang tidak ditemukan.'], 404);
        }

        if ($barang->status !== 'aktif') {
            return response()->json(['message' => 'Hanya barang dengan status aktif yang dapat diperpanjang.'], 400);
        }

        if (Carbon::parse($barang->tglSelesai)->isAfter(now())) {
            return response()->json(['message' => 'Masa penitipan belum habis, belum bisa diperpanjang.'], 400);
        }

        $barang->tglSelesai = Carbon::parse($barang->tglSelesai)->addDays(30);
        $barang->status = 'diperpanjang';
        $barang->save();

        return response()->json(['message' => 'Barang berhasil diperpanjang.', 'data' => $barang]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'penitipID'    => 'required|exists:penitips,penitipID',
            'namaProduk'   => 'required|string|max:255',
            'deskripsi'    => 'nullable|string',
            'harga'        => 'required|numeric',
            'kategoriID'   => 'nullable|exists:kategori,idKategori',
            'status'       => 'required|in:aktif,menunggu diambil,diambil,didonasikan,diperpanjang',
            'tglMulai'     => 'required|date',
            'tglSelesai'   => 'required|date|after_or_equal:tglMulai',
            'garansi'      => 'nullable|date',
            'gambar1'      => 'required|image|mimes:jpeg,jpg,png|max:2048',
            'gambar2'      => 'required|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $gambar1Path = $request->file('gambar1')->store('barang_foto', 'public');
        $gambar2Path = $request->file('gambar2')->store('barang_foto', 'public');

        $barang = Barang::create([
            'donasiID'     => $request->donasiID ?? null,
            'penitipID'    => $request->penitipID,
            'namaProduk'   => $request->namaProduk,
            'deskripsi'    => $request->deskripsi,
            'harga'        => $request->harga,
            'kategoriID'   => $request->kategoriID,
            'status'       => $request->status,
            'tglMulai'     => $request->tglMulai,
            'tglSelesai'   => $request->tglSelesai,
            'garansi'      => $request->garansi,
            'gambar'       => $gambar1Path,
            'gambar2'      => $gambar2Path,
        ]);

        return response()->json(['message' => 'Barang berhasil ditambahkan', 'barang' => $barang], 201);
    }

    public function konfirmasiAmbil($id)
    {
        $barang = Barang::find($id);
        if (!$barang) {
            return response()->json(['message' => 'Barang tidak ditemukan'], 404);
        }

        if ($barang->status === 'diambil' || $barang->status === 'didonasikan') {
            return response()->json(['message' => 'Barang sudah tidak dapat dikonfirmasi'], 403);
        }

        if (Carbon::parse($barang->tglSelesai)->isFuture()) {
            return response()->json(['message' => 'Masa penitipan belum habis'], 400);
        }

        $barang->status = 'menunggu diambil';
        $barang->save();

        return response()->json(['message' => 'Konfirmasi berhasil, status menjadi menunggu diambil', 'data' => $barang]);
    }

    public function donasikan($id)
    {
        $barang = Barang::find($id);
        if (!$barang) {
            return response()->json(['message' => 'Barang tidak ditemukan'], 404);
        }

        if (Carbon::parse($barang->tglSelesai)->isFuture()) {
            return response()->json(['message' => 'Masa penitipan belum habis'], 400);
        }

        $barang->status = 'didonasikan';
        $barang->save();

        return response()->json(['message' => 'Barang telah didonasikan.', 'data' => $barang]);
    }

    public function markAsTaken($id)
    {
        $barang = Barang::find($id);

        if (!$barang) {
            return response()->json(['message' => 'Barang tidak ditemukan'], 404);
        }

        if ($barang->status !== 'menunggu diambil') {
            return response()->json(['message' => 'Barang belum dikonfirmasi untuk diambil.'], 400);
        }

        $barang->status = 'diambil';
        $barang->tglSelesai = now();
        $barang->updated_at = now();
        $barang->save();

        return response()->json([
            'message' => 'Barang berhasil ditandai sebagai sudah diambil.',
            'data' => $barang
        ]);
    }

    public function semuaMenungguDiambil()
    {
        $barangs = Barang::with(['penitip', 'kategori'])
            ->where('status', 'menunggu diambil')
            ->get()
            ->map(function ($b) {
                return [
                    'idProduk'     => $b->idProduk,
                    'donasiID'     => $b->donasiID,
                    'penitipID'    => $b->penitipID,
                    'namaProduk'   => $b->namaProduk,
                    'namaPenitip'  => $b->penitip->nama ?? 'Tidak ditemukan',
                    'kategoriID'   => $b->kategoriID,
                    'namaKategori' => $b->kategori ? $b->kategori->namaKategori : null,
                    'status'       => $b->status,
                    'tglSelesai'   => $b->tglSelesai,
                    'gambar_url'   => $b->gambar ? url('storage/' . $b->gambar) : null,
                    'gambar2_url'  => $b->gambar2 ? url('storage/' . $b->gambar2) : null,
                    'created_at'   => $b->created_at,
                    'updated_at'   => $b->updated_at,
                ];
            });

        return response()->json($barangs);
    }

    public function tandaiDiambil($id)
    {
        $barang = Barang::find($id);

        if (!$barang) {
            return response()->json(['message' => 'Barang tidak ditemukan'], 404);
        }

        if ($barang->status !== 'menunggu diambil') {
            return response()->json(['message' => 'Barang tidak dalam status menunggu diambil.'], 400);
        }

        $barang->status = 'diambil';
        $barang->tglSelesai = now();
        $barang->updated_at = now();
        $barang->save();

        return response()->json(['message' => 'Barang berhasil ditandai sebagai diambil.', 'data' => $barang]);
    }

    public function gudangBarangDiambil()
    {
        $barangs = Barang::with(['penitip', 'kategori'])
            ->whereIn('status', ['menunggu diambil', 'diambil'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($b) {
                return [
                    'idProduk'     => $b->idProduk,
                    'donasiID'     => $b->donasiID,
                    'penitipID'    => $b->penitipID,
                    'namaProduk'   => $b->namaProduk,
                    'namaPenitip'  => $b->penitip->nama ?? 'Tidak ditemukan',
                    'kategoriID'   => $b->kategoriID,
                    'namaKategori' => $b->kategori ? $b->kategori->namaKategori : null,
                    'status'       => $b->status,
                    'tglSelesai'   => $b->tglSelesai,
                    'gambar_url'   => $b->gambar ? url('storage/' . $b->gambar) : null,
                    'gambar2_url'  => $b->gambar2 ? url('storage/' . $b->gambar2) : null,
                    'created_at'   => $b->created_at,
                    'updated_at'   => $b->updated_at,
                ];
            });

        return response()->json($barangs);
    }

    public function update(Request $request, $id)
    {
        $barang = Barang::find($id);

        if (!$barang) {
            return response()->json(['message' => 'Barang tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'penitipID'    => 'sometimes|exists:penitips,penitipID',
            'namaProduk'   => 'sometimes|string|max:255',
            'deskripsi'    => 'sometimes|string',
            'harga'        => 'sometimes|numeric',
            'kategoriID'   => 'sometimes|nullable|exists:kategori,idKategori',
            'status'       => 'sometimes|in:aktif,menunggu diambil,diambil,didonasikan,diperpanjang',
            'tglMulai'     => 'sometimes|date',
            'tglSelesai'   => 'sometimes|date|after_or_equal:tglMulai',
            'garansi'      => 'sometimes|date',
            'gambar1'      => 'sometimes|image|mimes:jpeg,jpg,png|max:2048',
            'gambar2'      => 'sometimes|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $data = $request->only(['penitipID', 'namaProduk', 'deskripsi', 'harga', 'kategoriID', 'status', 'tglMulai', 'tglSelesai', 'garansi']);

        if ($request->hasFile('gambar1')) {
            if ($barang->gambar) {
                Storage::disk('public')->delete($barang->gambar);
            }
            $data['gambar'] = $request->file('gambar1')->store('barang_foto', 'public');
        }

        if ($request->hasFile('gambar2')) {
            if ($barang->gambar2) {
                Storage::disk('public')->delete($barang->gambar2);
            }
            $data['gambar2'] = $request->file('gambar2')->store('barang_foto', 'public');
        }

        $barang->update($data);

        return response()->json(['message' => 'Barang berhasil diupdate', 'data' => $barang]);
    }

    public function destroy($id)
    {
        $barang = Barang::find($id);
        if (!$barang) {
            return response()->json(['message' => 'Barang tidak ditemukan'], 404);
        }

        if ($barang->gambar) {
            Storage::disk('public')->delete($barang->gambar);
        }
        if ($barang->gambar2) {
            Storage::disk('public')->delete($barang->gambar2);
        }
        $barang->delete();

        return response()->json(['message' => 'Barang berhasil dihapus']);
    }

    public function checkExpiredAndDonate()
{
    $currentDate = Carbon::now();
    $barangs = Barang::where('status', '!=', 'terjual')
        ->where('status', '!=', 'didonasikan')
        ->where('status', '!=', 'diperpanjang')
        ->where('status', '!=', 'aktif')
        ->get();

    $updated = false;
    foreach ($barangs as $barang) {
        $tglSelesai = Carbon::parse($barang->tglSelesai);
        $daysSinceExpired = $currentDate->diffInDays($tglSelesai);

        if ($daysSinceExpired > 7) {
            $barang->status = 'didonasikan'; 
            $barang->save();
            $updated = true;
            Log::info("Barang ID {$barang->idProduk} diubah ke 'didonasikan' karena melebihi 7 hari setelah {$barang->tglSelesai}");
        }
    }

    if ($updated) {
        return response()->json(['message' => 'Status barang yang kedaluwarsa diperbarui ke didonasikan.']);
    } else {
        return response()->json(['message' => 'Tidak ada barang yang perlu diubah statusnya.']);
    }
}

    public function getMonthlySales(Request $request)
    {
        $year = $request->query('year', Carbon::now()->year);

        $salesData = Barang::where('status', 'terjual')
            ->whereYear('updated_at', $year)
            ->selectRaw('MONTH(updated_at) as month, COUNT(*) as jumlahTerjual, SUM(harga) as jumlahKotor')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                $monthName = Carbon::create()->month($item->month)->translatedFormat('F');
                return [
                    'bulan' => $monthName,
                    'jumlahTerjual' => $item->jumlahTerjual,
                    'jumlahKotor' => $item->jumlahKotor,
                ];
            });

        // Fill in missing months with zero values
        $allMonths = collect(range(1, 12))->map(function ($month) use ($year, $salesData) {
            $monthName = Carbon::create()->month($month)->translatedFormat('F');
            $existing = $salesData->firstWhere('bulan', $monthName);
            return $existing ?: [
                'bulan' => $monthName,
                'jumlahTerjual' => 0,
                'jumlahKotor' => 0,
            ];
        });

        return response()->json($allMonths);
    }

    public function getMonthlyCommissions(Request $request)
    {
        $month = $request->query('month');
        $year = $request->query('year', Carbon::now()->year);

        // Validate month input and map to month number
        $monthMap = [
            'Januari' => 1,
            'Februari' => 2,
            'Maret' => 3,
            'April' => 4,
            'Mei' => 5,
            'Juni' => 6,
            'Juli' => 7,
            'Agustus' => 8,
            'September' => 9,
            'Oktober' => 10,
            'November' => 11,
            'Desember' => 12,
        ];

        if (!$month || !array_key_exists($month, $monthMap)) {
            return response()->json(['message' => 'Bulan tidak valid'], 400);
        }

        $monthNumber = $monthMap[$month];

        $barangs = Barang::where('status', 'terjual')
            ->whereYear('updated_at', $year)
            ->whereMonth('updated_at', $monthNumber)
            ->with('penitip')
            ->get()
            ->map(function ($b) {
                $masukDate = Carbon::parse($b->tglMulai);
                $lakuDate = Carbon::parse($b->updated_at);
                $daysDiff = $masukDate->diffInDays($lakuDate);

                $baseKomisiReUseMart = $b->harga * 0.20; // 20% base commission
                $komisiHunter = 0;
                $bonusPenitip = 0;

                if ($daysDiff < 7) {
                    $komisiHunter = $b->hasHunter ? $b->harga * 0.05 : 0; // 5% if hasHunter is 1
                    $bonusPenitip = $baseKomisiReUseMart * 0.10; // 10% bonus for penitip
                } else {
                    $baseKomisiReUseMart = $b->harga * 0.30; // 30% if >= 7 days
                }

                $komisiReUseMart = $baseKomisiReUseMart - $bonusPenitip;

                return [
                    'idProduk' => $b->idProduk,
                    'kode' => 'P' . str_pad($b->idProduk, 3, '0', STR_PAD_LEFT), // Generate code like P001
                    'namaProduk' => $b->namaProduk,
                    'harga' => $b->harga,
                    'tanggalMasuk' => $b->tglMulai->format('d/m/Y'),
                    'tanggalLaku' => $lakuDate->format('d/m/Y'),
                    'hasHunter' => (bool)$b->hasHunter, // Convert tinyint to boolean
                    'idPenitip' => $b->penitipID,
                    'namaPenitip' => $b->penitip->nama ?? 'Tidak ditemukan',
                    'komisiHunter' => $komisiHunter,
                    'komisiReUseMart' => $komisiReUseMart,
                    'bonusPenitip' => $bonusPenitip,
                ];
            });

        return response()->json($barangs);
    }

    public function gudangStok()
    {
        $currentDate = Carbon::now();
        $barangs = Barang::whereIn('status', ['aktif', 'diperpanjang'])
            // ->where(function ($query) use ($currentDate) {
            //     $query->where('tglSelesai', '>=', $currentDate)
            //           ->orWhereNull('tglSelesai');
            // })
            ->with('penitip')
            ->get()
            ->map(function ($b) {
                return [
                    'idProduk' => $b->idProduk,
                    'kode' => 'P' . str_pad($b->idProduk, 3, '0', STR_PAD_LEFT),
                    'namaProduk' => $b->namaProduk,
                    'penitipID' => $b->penitipID,
                    'namaPenitip' => $b->penitip->nama ?? 'Tidak ditemukan',
                    'tanggalMasuk' => $b->tglMulai ? Carbon::parse($b->tglMulai)->format('d/m/Y') : '-',
                    'perpanjangan' => $b->status === 'diperpanjang',
                    'idHunter' => null, // Adjust if hunter data exists in a related table
                    'namaHunter' => null, // Adjust if hunter data exists in a related table
                    'harga' => $b->harga,
                ];
            });

        return response()->json($barangs);
    }
}