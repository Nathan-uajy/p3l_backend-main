<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Barang;
use Carbon\Carbon;

class GudangController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'penitipID'    => 'required|exists:penitips,penitipID',
            'namaProduk'   => 'required|string|max:255',
            'deskripsi'    => 'nullable|string',
            'harga'        => 'required|numeric',
            'kategoriID'   => 'nullable|exists:kategori,idKategori',
            'garansi'      => 'nullable|date',
            'gambar1'      => 'required|image|mimes:jpeg,jpg,png|max:10240',
            'gambar2'      => 'required|image|mimes:jpeg,jpg,png|max:10240',
            'kurirID'      => 'nullable|exists:pegawais,pegawaiID',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $tglMasuk = Carbon::now();
            $tglSelesai = $tglMasuk->copy()->addDays(30);

            $gambar1 = $request->file('gambar1')->store('barang_foto', 'public');
            $gambar2 = $request->file('gambar2')->store('barang_foto', 'public');

            $barang = Barang::create([
                'penitipID'   => $request->penitipID,
                'namaProduk'  => $request->namaProduk,
                'deskripsi'   => $request->deskripsi,
                'harga'       => $request->harga,
                'kategoriID'  => $request->kategoriID,
                'status'      => 'aktif',
                'tglMulai'    => $tglMasuk,
                'tglSelesai'  => $tglSelesai,
                'garansi'     => $request->garansi,
                'kurirID'     => $request->kurirID,
                'gambar'      => $gambar1,
                'gambar2'     => $gambar2,
            ]);

            $barang->load(['penitip', 'kategori', 'kurir']);
            $responseData = [
                'idProduk'     => $barang->idProduk,
                'namaProduk'   => $barang->namaProduk,
                'deskripsi'    => $barang->deskripsi,
                'harga'        => $barang->harga,
                'kategoriID'   => $barang->kategoriID,
                'namaKategori' => $barang->kategori ? $barang->kategori->namaKategori : null,
                'kurirID'      => $barang->kurirID,
                'kurirNama'    => $barang->kurir ? $barang->kurir->nama : null,
                'status'       => $barang->status,
                'garansi'      => $barang->garansi,
                'tglMulai'     => $barang->tglMulai,
                'tglSelesai'   => $barang->tglSelesai,
                'namaPenitip'  => $barang->penitip ? $barang->penitip->nama : null,
                'gambar_url'   => $barang->gambar ? url('storage/' . $barang->gambar) : null,
                'gambar2_url'  => $barang->gambar2 ? url('storage/' . $barang->gambar2) : null,
            ];

            return response()->json(['message' => 'Barang berhasil ditambahkan', 'data' => $responseData], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating barang: ' . $e->getMessage());
            if (isset($gambar1)) Storage::disk('public')->delete($gambar1);
            if (isset($gambar2)) Storage::disk('public')->delete($gambar2);
            return response()->json(['message' => 'Terjadi kesalahan saat menambahkan barang: ' . $e->getMessage()], 500);
        }
    }

    public function index()
    {
        try {
            $barangs = Barang::with(['penitip', 'kategori', 'kurir'])->get()->map(function ($b) {
                return [
                    'idProduk'     => $b->idProduk,
                    'namaProduk'   => $b->namaProduk,
                    'deskripsi'    => $b->deskripsi,
                    'harga'        => $b->harga,
                    'kategoriID'   => $b->kategoriID,
                    'namaKategori' => $b->kategori ? $b->kategori->namaKategori : null,
                    'kurirID'      => $b->kurirID,
                    'kurirNama'    => $b->kurir ? $b->kurir->nama : null,
                    'status'       => $b->status,
                    'garansi'      => $b->garansi,
                    'tglMulai'     => $b->tglMulai,
                    'tglSelesai'   => $b->tglSelesai,
                    'namaPenitip'  => $b->penitip ? $b->penitip->nama : null,
                    'gambar_url'   => $b->gambar ? url('storage/' . $b->gambar) : null,
                    'gambar2_url'  => $b->gambar2 ? url('storage/' . $b->gambar2) : null,
                ];
            });

            return response()->json($barangs);
        } catch (\Exception $e) {
            \Log::error('Error fetching barangs: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat mengambil data barang: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $barang = Barang::with(['penitip', 'kategori', 'kurir'])->find($id);

            if (!$barang) {
                return response()->json(['message' => 'Barang tidak ditemukan'], 404);
            }

            $responseData = [
                'idProduk'     => $barang->idProduk,
                'namaProduk'   => $barang->namaProduk,
                'deskripsi'    => $barang->deskripsi,
                'harga'        => $barang->harga,
                'kategoriID'   => $barang->kategoriID,
                'namaKategori' => $barang->kategori ? $barang->kategori->namaKategori : null,
                'kurirID'      => $barang->kurirID,
                'kurirNama'    => $barang->kurir ? $barang->kurir->nama : null,
                'status'       => $barang->status,
                'garansi'      => $barang->garansi,
                'tglMulai'     => $barang->tglMulai,
                'tglSelesai'   => $barang->tglSelesai,
                'namaPenitip'  => $barang->penitip ? $barang->penitip->nama : null,
                'gambar_url'   => $barang->gambar ? url('storage/' . $barang->gambar) : null,
                'gambar2_url'  => $barang->gambar2 ? url('storage/' . $barang->gambar2) : null,
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            \Log::error('Error fetching barang: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat mengambil detail barang: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $barang = Barang::find($id);

        if (!$barang) {
            return response()->json(['message' => 'Barang tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'penitipID'    => 'sometimes|required|exists:penitips,penitipID',
            'namaProduk'   => 'sometimes|string|max:255',
            'deskripsi'    => 'sometimes|string',
            'harga'        => 'sometimes|numeric',
            'kategoriID'   => 'sometimes|nullable|exists:kategori,idKategori',
            'status'       => 'sometimes|in:aktif,menunggu diambil,diambil,didonasikan,diperpanjang',
            'garansi'      => 'sometimes|date',
            'kurirID'      => 'sometimes|nullable|exists:pegawais,pegawaiID',
            'tglMulai'     => 'sometimes|date',
            'tglSelesai'   => 'sometimes|date|after_or_equal:tglMulai',
            'gambar1'      => 'sometimes|image|mimes:jpeg,jpg,png|max:10240',
            'gambar2'      => 'sometimes|image|mimes:jpeg,jpg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $data = $request->only(['penitipID', 'namaProduk', 'deskripsi', 'harga', 'kategoriID', 'status', 'garansi', 'tglMulai', 'tglSelesai']);
            $data['kurirID'] = $request->kurirID === '' ? null : $request->kurirID;

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

            $barang->load(['penitip', 'kategori', 'kurir']);
            $responseData = [
                'idProduk'     => $barang->idProduk,
                'namaProduk'   => $barang->namaProduk,
                'deskripsi'    => $barang->deskripsi,
                'harga'        => $barang->harga,
                'kategoriID'   => $barang->kategoriID,
                'namaKategori' => $barang->kategori ? $barang->kategori->namaKategori : null,
                'kurirID'      => $barang->kurirID,
                'kurirNama'    => $barang->kurir ? $barang->kurir->nama : null,
                'status'       => $barang->status,
                'garansi'      => $barang->garansi,
                'tglMulai'     => $barang->tglMulai,
                'tglSelesai'   => $barang->tglSelesai,
                'namaPenitip'  => $barang->penitip ? $barang->penitip->nama : null,
                'gambar_url'   => $barang->gambar ? url('storage/' . $barang->gambar) : null,
                'gambar2_url'  => $barang->gambar2 ? url('storage/' . $barang->gambar2) : null,
            ];

            return response()->json(['message' => 'Barang berhasil diupdate', 'data' => $responseData]);
        } catch (\Exception $e) {
            \Log::error('Error updating barang: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat mengupdate barang: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $barang = Barang::find($id);
        if (!$barang) {
            return response()->json(['message' => 'Barang tidak ditemukan'], 404);
        }

        try {
            $gambar1 = $barang->gambar;
            $gambar2 = $barang->gambar2;

            $barang->delete();

            if ($gambar1) {
                Storage::disk('public')->delete($gambar1);
            }
            if ($gambar2) {
                Storage::disk('public')->delete($gambar2);
            }

            return response()->json(['message' => 'Barang berhasil dihapus']);
        } catch (\Exception $e) {
            \Log::error('Error deleting barang: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat menghapus barang: ' . $e->getMessage()], 500);
        }
    }

    public function search(Request $request)
    {
        $q = $request->query('q');

        if (empty($q)) {
            return response()->json(['message' => 'Parameter pencarian (q) diperlukan'], 400);
        }

        try {
            $results = Barang::with(['kategori', 'penitip', 'kurir'])
                ->where(function ($query) use ($q) {
                    $query->where('namaProduk', 'like', "%$q%")
                          ->orWhere('deskripsi', 'like', "%$q%")
                          ->orWhere('status', 'like', "%$q%")
                          ->orWhere('garansi', 'like', "%$q%")
                          ->orWhereHas('kategori', function ($kategoriQuery) use ($q) {
                              $kategoriQuery->where('namaKategori', 'like', "%$q%");
                          })
                          ->orWhereHas('penitip', function ($penitipQuery) use ($q) {
                              $penitipQuery->where('nama', 'like', "%$q%");
                          });
                })
                ->get()
                ->map(function ($b) {
                    return [
                        'idProduk'     => $b->idProduk,
                        'namaProduk'   => $b->namaProduk,
                        'deskripsi'    => $b->deskripsi,
                        'harga'        => $b->harga,
                        'kategoriID'   => $b->kategoriID,
                        'namaKategori' => $b->kategori ? $b->kategori->namaKategori : null,
                        'kurirID'      => $b->kurirID,
                        'kurirNama'    => $b->kurir ? $b->kurir->nama : null,
                        'status'       => $b->status,
                        'garansi'      => $b->garansi,
                        'tglMulai'     => $b->tglMulai,
                        'tglSelesai'   => $b->tglSelesai,
                        'namaPenitip'  => $b->penitip ? $b->penitip->nama : null,
                        'gambar_url'   => $b->gambar ? url('storage/' . $b->gambar) : null,
                        'gambar2_url'  => $b->gambar2 ? url('storage/' . $b->gambar2) : null,
                    ];
            });

            return response()->json($results);
        } catch (\Exception $e) {
            \Log::error('Error searching barang: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat mencari barang: ' . $e->getMessage()], 500);
        }
    }
}