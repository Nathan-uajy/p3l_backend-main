<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaksi;

class NotaKurirController extends Controller
{
    public function getNotaData($transaksiID)
{
    $transaksi = Transaksi::with([
        'pembeli',
        'alamat',
        'detailTransaksis.produk',
        'penjadwalan.pegawai'
    ])->findOrFail($transaksiID);

    // Filter hanya penjadwalan bertipe 'pengiriman'
    $penjadwalanPengiriman = collect($transaksi->penjadwalan)
        ->where('tipe', 'pengiriman')
        ->values();
    $transaksi->penjadwalan = $penjadwalanPengiriman;

    // Hitung total harga barang (asumsi 1 barang per transaksi)
    $total = $transaksi->detailTransaksis->sum(function ($detail) {
        return $detail->produk->harga ?? 0;
    });

    // Ambil poin loyalitas pembeli saat ini
    $poinPembeli = $transaksi->pembeli->poinLoyalitas ?? 0;

    // Atur nilai per poin dan batasi penggunaan maksimal sesuai nilai belanja
    $nilaiPerPoin = 100;
    $maksimalPotongan = floor($total / $nilaiPerPoin);
    $poinDigunakan = min($poinPembeli, $maksimalPotongan);

    // Hitung nilai potongan dan total bayar
    $potongan = $poinDigunakan * $nilaiPerPoin;
    $totalBayar = max($total - $potongan, 0);

    // Hitung poin reward: 1 poin per Rp10.000
    $poinReward = floor($total / 10000);
    $bonus = $total > 500000 ? floor($poinReward * 0.20) : 0;
    $totalPoinBaru = $poinReward + $bonus;

    // Hitung total poin akhir pembeli (setelah pengurangan dan penambahan)
    $poinAkhir = $poinPembeli - $poinDigunakan + $totalPoinBaru;

    // Sisipkan ke objek response
    $transaksi->perhitungan = [
        'total' => $total,
        'poinSebelum' => $poinPembeli,
        'nilaiPerPoin' => $nilaiPerPoin,
        'poinDigunakan' => $poinDigunakan,
        'potongan' => $potongan,
        'totalBayar' => $totalBayar,
        'poinReward' => $poinReward,
        'bonus' => $bonus,
        'poinDidapatkan' => $totalPoinBaru,
        'poinAkhir' => $poinAkhir,
    ];

    $data = $transaksi->toArray(); // Konversi ke array agar property dinamis ikut
    $data['perhitungan'] = $transaksi->perhitungan; // Sisipkan kembali secara manual

    return response()->json($transaksi);
}



    public function daftarTransaksiSiap()
    {
        $transaksis = Transaksi::whereIn('status', ['siap dikirim'])
            ->with('pembeli')
            ->get();

        return response()->json($transaksis);
    }

}
