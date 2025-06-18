<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Barang;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Transaksi;
use App\Models\Pembeli;

class BarangController extends Controller
{
    // Fitur 52 - Ambil semua barang milik penitip tertentu
    public function getByPenitip($penitipID)
    {
        $barangs = Barang::where('penitipID', $penitipID)->get()->map(function ($b) {
            return [
                'idProduk'     => $b->idProduk,
                'namaProduk'   => $b->namaProduk,
                'deskripsi'    => $b->deskripsi,
                'harga'        => $b->harga,
                'kategori'     => $b->kategori,
                'status'       => $b->status,
                'garansi'      => $b->garansi,
                'tglMulai'     => $b->tglMulai,
                'tglSelesai'   => $b->tglSelesai,
                'gambar_url'   => $b->gambar ? url('storage/' . $b->gambar) : null,
                'gambar2_url'  => $b->gambar2 ? url('storage/' . $b->gambar2) : null,
            ];
        }
        );
    
        return response()->json($barangs);
    }

    // Menampilkan detail barang barang-transaksi
    public function show($id)
    {
        // Cari detail transaksi berdasarkan transaksiID
        $detail = DB::table('detail_transaksis')->where('transaksiID', $id)->first();

        if (!$detail) {
            return response()->json(['message' => 'Detail transaksi tidak ditemukan'], 404);
        }

        $data = DB::table('detail_transaksis')
            ->join('transaksis', 'transaksis.transaksiID', '=', 'detail_transaksis.transaksiID')
            ->join('pembelis', 'pembelis.pembeliID', '=', 'transaksis.pembeliID')
            ->join('barangs', 'barangs.idProduk', '=', 'detail_transaksis.produkID')
            ->join('penitips', 'penitips.penitipID', '=', 'barangs.penitipID')
            ->where('detail_transaksis.transaksiID', $id)
            ->select(
                'barangs.idProduk as idProduk',
                'barangs.namaProduk',
                'barangs.harga',
                'barangs.gambar as gambar1',
                'barangs.gambar2',
                'penitips.nama as namaPenitip',
                'pembelis.nama as namaPembeli',
                'pembelis.alamat as alamatPengiriman',
                'transaksis.waktu_transaksi as tglTransaksi',
                'transaksis.status as statusTransaksi',
                'transaksis.tipe_transaksi as tipe_transaksi'
            )
            ->first();

        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($data);
    }
    public function showDetail($id)
    {
        $barang = Barang::find($id);
        if (!$barang) {
            return response()->json(['message' => 'Barang tidak ditemukan'], 404);
        }
        
        return response()->json([
            'idProduk'     => $barang->idProduk,
            'namaProduk'   => $barang->namaProduk,
            'deskripsi'    => $barang->deskripsi,
            'harga'        => $barang->harga,
            'kategori'     => $barang->kategori,
            'status'       => $barang->status,
            'garansi'      => $barang->garansi,
            'tglMulai'     => $barang->tglMulai,
            'tglSelesai'   => $barang->tglSelesai,
            'gambar_url'   => $barang->gambar ? url('storage/' . $barang->gambar) : null,
            'gambar2_url'  => $barang->gambar2 ? url('storage/' . $barang->gambar2) : null,
        ]);
    }
    
    // Fitur 53 - Pencarian barang
    public function search(Request $request)
    {
        $q = $request->query('q');
        $penitipID = $request->query('penitipID'); // Ambil ID penitip dari frontend

        $barang = Barang::where('penitipID', $penitipID)
            ->where(function ($query) use ($q) {
                $query->where('namaProduk', 'like', "%$q%")
                    ->orWhere('kategori', 'like', "%$q%")
                    ->orWhere('status', 'like', "%$q%")
                    ->orWhere('garansi', 'like', "%$q%");
            })
            ->get();

        // Tambahkan URL gambar agar bisa ditampilkan di frontend
        $data = $barang->map(function ($item) {
            return [
                'idProduk' => $item->idProduk,
                'namaProduk' => $item->namaProduk,
                'deskripsi' => $item->deskripsi,
                'harga' => $item->harga,
                'kategori' => $item->kategori,
                'status' => $item->status,
                'garansi' => $item->garansi,
                'tglMulai' => $item->tglMulai,
                'tglSelesai' => $item->tglSelesai,
                'gambar_url' => $item->gambar ? url('storage/' . $item->gambar) : null,
                'gambar2_url' => $item->gambar2 ? url('storage/' . $item->gambar2) : null,
            ];
        });

        return response()->json($data);
    }

    // Perpanjang masa penitipan 30 hari
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

        return response()->json(['message' => 'Barang berhasil diperpanjang.']);
    }

    // Menambahkan barang baru dengan 2 gambar
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'penitipID'    => 'required|exists:penitips,penitipID',
            'namaProduk'   => 'required|string|max:255',
            'deskripsi'    => 'nullable|string',
            'harga'        => 'required|numeric',
            'kategori'     => 'nullable|string|max:255',
            'status'       => 'required|string|max:255',
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
            'penitipID'   => $request->penitipID,
            'namaProduk'  => $request->namaProduk,
            'deskripsi'   => $request->deskripsi,
            'harga'       => $request->harga,
            'kategori'    => $request->kategori,
            'status'      => $request->status,
            'tglMulai'    => $request->tglMulai,
            'tglSelesai'  => $request->tglSelesai,
            'garansi'     => $request->garansi,
            'gambar'      => $gambar1Path,
            'gambar2'     => $gambar2Path,
        ]);

        return response()->json(['message' => 'Barang berhasil ditambahkan', 'barang' => $barang]);
    }

    public function konfirmasiAmbil($id)
    {
        $barang = Barang::find($id);
        if (!$barang) {
            return response()->json(['message' => 'Barang tidak ditemukan'], 404);
        }

        // Validasi jika sudah diambil, didonasikan, atau belum masa habis
        if ($barang->status === 'diambil' || $barang->status === 'didonasikan') {
            return response()->json(['message' => 'Barang sudah tidak dapat dikonfirmasi'], 403);
        }

        if (Carbon::parse($barang->tglSelesai)->isFuture()) {
            return response()->json(['message' => 'Masa penitipan belum habis'], 400);
        }

        $barang->status = 'menunggu diambil';
        $barang->save();

        return response()->json(['message' => 'Konfirmasi berhasil, status menjadi menunggu diambil']);
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

        return response()->json(['message' => 'Barang telah didonasikan.']);
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
        $barang->tglSelesai = now(); // Ini adalah tanggal selesai penitipan
        $barang->updated_at = now();
        $barang->save();

        return response()->json([
            'message' => 'Barang berhasil ditandai sebagai sudah diambil.',
            'data' => $barang
        ]);
    }

    // Menampilkan semua barang dengan status 'menunggu diambil' (untuk Pegawai Gudang)
    public function semuaMenungguDiambil()
    {
        $barang = \App\Models\Barang::with('penitip')
            ->where('status', 'menunggu diambil')
            ->get()
            ->map(function ($b) {
                return [
                    'idProduk'     => $b->idProduk,
                    'namaProduk'   => $b->namaProduk,
                    'namaPenitip'  => $b->penitip->nama ?? 'Tidak ditemukan',
                    'status'       => $b->status,
                    'tglSelesai'   => $b->tglSelesai,
                    'gambar_url'   => $b->gambar ? url('storage/' . $b->gambar) : null,
                    'gambar2_url'  => $b->gambar2 ? url('storage/' . $b->gambar2) : null,
                ];
            });

        return response()->json($barang);
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

        return response()->json(['message' => 'Barang berhasil ditandai sebagai diambil.']);
    }

    // Menampilkan barang dengan status 'menunggu diambil' DAN 'diambil'
    public function gudangBarangDiambil()
    {
        $barang = Barang::with('penitip')
            ->whereIn('status', ['menunggu diambil', 'diambil']) // tampilkan keduanya
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($b) {
                return [
                    'idProduk'     => $b->idProduk,
                    'namaProduk'   => $b->namaProduk,
                    'namaPenitip'  => $b->penitip->nama ?? 'Tidak ditemukan',
                    'status'       => $b->status,
                    'tglSelesai'   => $b->tglSelesai,
                    'gambar_url'   => $b->gambar ? url('storage/' . $b->gambar) : null,
                    'gambar2_url'  => $b->gambar2 ? url('storage/' . $b->gambar2) : null,
                ];
            });

        return response()->json($barang);
    }

    public function available()
    {
        try{
        $barang = Barang::where('status', 'aktif')->get()->map(function ($b) {
            return [
                'idProduk'     => $b->idProduk,
                'namaProduk'   => $b->namaProduk,
                'deskripsi'    => $b->deskripsi,
                'harga'        => $b->harga,
                'kategori'     => $b->kategori,
                'status'       => $b->status,
                'gambar_url'   => $b->gambar ? url('storage/' . $b->gambar) : null,
                'gambar2_url'  => $b->gambar2 ? url('storage/' . $b->gambar2) : null,
                'tglMulai'     => $b->tglMulai,
                'tglSelesai'   => $b->tglSelesai,
                'garansi'      => $b->garansi,
            ];
        });

        return response()->json($barang);
    } catch (\Exception $e) {
        \Log::error('❌ ERROR di BarangController@available: ' . $e->getMessage());
        return response()->json(['error' => 'Internal Server Error'], 500);
    }
    }

    public function showDetailMobile($id)
{
    try {
        $barang = \App\Models\Barang::find($id);

        if (!$barang) {
            return response()->json(['message' => '❌ Barang tidak ditemukan'], 404);
        }

        return response()->json([
    'idProduk' => $barang->idProduk,
    'namaProduk' => $barang->namaProduk,
    'deskripsi' => $barang->deskripsi,
    'harga' => $barang->harga,
    'kategori' => $barang->kategori,
    'status' => $barang->status,
    'gambar_url' => $barang->gambar ? url('storage/' . $barang->gambar) : null,
    'gambar2_url' => $barang->gambar2 ? url('storage/' . $barang->gambar2) : null,
    'tglMulai' => $barang->tglMulai ? date('Y-m-d', strtotime($barang->tglMulai)) : null,
    'tglSelesai' => $barang->tglSelesai ? date('Y-m-d', strtotime($barang->tglSelesai)) : null,
    'garansi' => $barang->garansi ? date('Y-m-d', strtotime($barang->garansi)) : null,
    'penitipID' => $barang->penitipID,
    'created_at' => $barang->created_at ? $barang->created_at->toDateTimeString() : null,
]);

    } catch (\Exception $e) {
        \Log::error('❌ ERROR @showDetailMobile: ' . $e->getMessage());
        return response()->json(['message' => 'Internal Server Error'], 500);
    }
}




}
