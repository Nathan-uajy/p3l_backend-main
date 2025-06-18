<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Pembeli;
use Illuminate\Support\Facades\DB;
use App\Models\Barang;
use App\Models\Penjadwalan;
use Illuminate\Support\Facades\Validator;
use App\Models\Komisi;
use Carbon\Carbon;

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
        return response()->json($transaksi, 201);
    }

    public function show($id)
    {
        return response()->json(Transaksi::with(['pembeli', 'penitip', 'alamat'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $transaksi = Transaksi::findOrFail($id);
        $transaksi->update($request->all());
        return response()->json($transaksi);
    }

    public function destroy($id)
    {
        Transaksi::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }

    // TransaksiController.php
    public function indexGudang()
    {
        $transaksi = DB::table('transaksis')
            ->join('detail_transaksis', 'transaksis.transaksiID', '=', 'detail_transaksis.transaksiID')
            ->join('barangs', 'detail_transaksis.produkID', '=', 'barangs.idProduk')
            ->join('penitips', 'barangs.penitipID', '=', 'penitips.id')
            ->whereIn('transaksis.status', ['siap dikirim', 'siap diambil' , 'diproses'])
            ->select(
                'barangs.idProduk',
                'barangs.namaProduk',
                'barangs.gambar as gambar1',
                'barangs.gambar2',
                'barangs.status as statusBarang',
                'penitips.nama as namaPenitip',
                'transaksis.status',
                'transaksis.waktu_transaksi as tglSelesai'
            )
            ->get();

        return response()->json($transaksi);
    }

    public function transaksiGudang()
    {
        $data = DB::table('transaksis')
            ->join('detail_transaksis', 'transaksis.transaksiID', '=', 'detail_transaksis.transaksiID')
            ->join('barangs', 'detail_transaksis.produkID', '=', 'barangs.idProduk')
            ->join('penitips', 'barangs.penitipID', '=', 'penitips.penitipID')
            ->join('pembelis', 'transaksis.pembeliID', '=', 'pembelis.pembeliID')
            ->whereIn('transaksis.tipe_transaksi', ['ambil', 'kirim']) // ✅ PERBAIKAN PENTING
            ->select(
                'transaksis.transaksiID as idTransaksi',
                'transaksis.tipe_transaksi as tipeTransaksi',
                'transaksis.status as statusTransaksi',
                'transaksis.waktu_transaksi as tglSelesai',
                'pembelis.nama as namaPembeli',
                'penitips.nama as namaPenitip',
                'barangs.namaProduk',
                'barangs.gambar as gambar1' // ✅ field yang benar dari struktur barangs
            )
            ->get();

        return response()->json($data);
    }


    public function updateStatus(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'status' => 'required|string'
        ]);

        // Update status di tabel transaksis
        $transaksi = Transaksi::findOrFail($id);
        $transaksi->status = $request->status;
        $transaksi->save();

        // Update status di tabel barangs berdasarkan detail_transaksi
        if (in_array($request->status, ['selesai', 'berhasil diambil'])) {
            $produkIDs = DB::table('detail_transaksis')
                ->where('transaksiID', $id)
                ->pluck('produkID');

            foreach ($produkIDs as $produkID) {
                $barang = Barang::find($produkID);
                $barang->status = $request->status == 'selesai' ? 'terjual' : 'diambil';
                $barang->save();
            }
        }

        return response()->json(['message' => 'Status transaksi dan barang berhasil diperbarui']);
    }

    public function transaksiGudangAmbil()
    {
        try {
            \Log::info('🔥 Masuk ke transaksiGudangAmbil');

            $transaksis = DB::table('transaksis')
                ->join('pembelis', 'transaksis.pembeliID', '=', 'pembelis.pembeliID')
                ->join('penitips', 'transaksis.penitipID', '=', 'penitips.penitipID')
                ->where('transaksis.tipe_transaksi', 'ambil')
                ->where('transaksis.status', 'diproses')
                ->select(
                    'transaksis.transaksiID',
                    'transaksis.status',
                    'transaksis.waktu_transaksi',
                    'pembelis.nama as namaPembeli',
                    'penitips.nama as namaPenitip'
                )
                ->get();

            \Log::info('✅ Transaksi pengambilan ditemukan:', ['data' => $transaksis]);

            return response()->json($transaksis);
        } catch (\Throwable $e) {
            \Log::error('❌ ERROR transaksiGudangAmbil: ' . $e->getMessage());
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function simpanKomisi($transaksiID)
    {
        // // 1. Cek apakah komisi sudah dihitung sebelumnya
        // $cek = Komisi::where('transaksiID', $transaksiID)->first();
        // if ($cek) {
        //     return response()->json([
        //         'message' => '❌ Komisi untuk transaksi ini sudah pernah dihitung.',
        //         'komisiID' => $cek->komisiID
        //     ], 409);
        // }

        // 2. Ambil transaksi lengkap + penjadwalan pengiriman + pegawai
        $transaksi = Transaksi::with([
            'penitip',
            'pembeli',
            'penjadwalanPengiriman.pegawai',
            'detailTransaksis.produk'
        ])->findOrFail($transaksiID);

        // 3. Hitung total harga produk
        $hargaTotal = $transaksi->detailTransaksis->sum(function ($item) {
            return $item->produk->harga ?? 0;
        });

        // 4. Cek apakah pegawai penjadwalan pengiriman adalah hunter
        $pegawai = $transaksi->penjadwalanPengiriman->pegawai ?? null;
        $isHunter = $pegawai && strtolower($pegawai->jabatan) === 'hunter';

        // 5. Cek apakah transaksi adalah perpanjangan
        $isPerpanjangan = $transaksi->status === 'diperpanjang';

        // 6. Hitung komisi
        $komisiHunter = $isHunter ? $hargaTotal * 0.05 : 0;
        $persentasePerusahaan = $isPerpanjangan
            ? ($isHunter ? 0.25 : 0.30)
            : ($isHunter ? 0.15 : 0.20);
        $komisiPerusahaan = $hargaTotal * $persentasePerusahaan;

        // 7. Cek bonus penitip jika terjual < 7 hari
        $tanggalMasuk = \Carbon\Carbon::parse($transaksi->created_at);
        $tanggalJual = \Carbon\Carbon::parse($transaksi->waktu_transaksi);
        $selisihHari = $tanggalMasuk->diffInDays($tanggalJual);

        $bonusPenitip = 0;
        if ($selisihHari < 7) {
            $bonusPenitip = $komisiPerusahaan * 0.10;
            $transaksi->penitip->increment('saldo', $bonusPenitip);
        }

        // 8. Hitung total komisi dan hasil bersih
        $jumlahKomisi = $komisiHunter + $komisiPerusahaan;
        $hasilBersih = $hargaTotal - $jumlahKomisi;

        // 9. Simpan ke tabel komisis
        Komisi::create([
            'transaksiID' => $transaksi->transaksiID,
            'penitipID' => $transaksi->penitipID,
            'komisi_hunter' => $komisiHunter,
            'komsi_perusahaan' => $komisiPerusahaan,
            'jumlahKomisi' => $jumlahKomisi,
            'persentase' => $persentasePerusahaan * 100,
            'tanggalKomisi' => now(),
        ]);

        // 10. Tambah saldo penitip
        $transaksi->penitip->increment('saldo', $hasilBersih);

        // 11. Hitung poin loyalitas pembeli
        $pembeli = $transaksi->pembeli;
        $poin = floor($hargaTotal / 10000);
        if ($hargaTotal > 500000) {
            $poin += floor($poin * 0.20);
        }
        $pembeli->increment('poinLoyalitas', $poin);

        // 12. Response
        return response()->json([
            'message' => '✅ Komisi dihitung dan disimpan.',
            'komisiPerusahaan' => $komisiPerusahaan,
            'komisiHunter' => $komisiHunter,
            'bonusPenitip' => $bonusPenitip,
            'saldoBersihPenitip' => $hasilBersih,
            'poinPembeliDitambahkan' => $poin,
            'hunter' => $isHunter ? ($pegawai->nama ?? 'hunter tanpa nama') : null
        ]);
    }

    public function cekKomisi($transaksiID)
    {
        $komisi = Komisi::where('transaksiID', $transaksiID)->first();

        if ($komisi) {
            return response()->json([
                'status' => '✅ Komisi sudah dihitung',
                'komisiID' => $komisi->komisiID,
                'jumlah' => $komisi->jumlahKomisi,
                'tanggal' => $komisi->tanggalKomisi,
                'hunter' => $komisi->komisi_hunter,
                'perusahaan' => $komisi->komsi_perusahaan,
            ]);
        } else {
            return response()->json([
                'status' => '❌ Komisi belum dihitung',
                'transaksiID' => $transaksiID,
            ]);
        }
    }

    public function updateStatusTransaksi(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        $transaksi = Transaksi::with('detailTransaksis')->findOrFail($id);
        $transaksi->status = $request->status;
        $transaksi->save();

        // Update semua barang yang terkait dengan transaksi ini
        foreach ($transaksi->detailTransaksis as $detail) {
            $produk = \App\Models\Barang::find($detail->produkID);
            if ($produk) {
                if ($request->status === 'selesai') {
                    $produk->status = 'terjual';
                } elseif ($request->status === 'hangus') {
                    $produk->status = 'expired';
                } elseif ($request->status === 'diperpanjang') {
                    $produk->status = 'diperpanjang';
                } else {
                    $produk->status = 'aktif'; // default atau fallback
                }
                $produk->save();
            }
        }

        return response()->json([
            'message' => '✅ Status transaksi & barang berhasil diupdate.',
            'statusBaru' => $request->status
        ]);
    }


}
