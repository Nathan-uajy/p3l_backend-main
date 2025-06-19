<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CekTransaksiHangus extends Command
{
    protected $signature = 'app:cek-transaksi-hangus';
    protected $description = 'Menandai transaksi pengambilan yang tidak diambil dalam 2 hari sebagai hangus dan barang jadi donasi.';

public function handle()
{
    $limitDate = now()->subDays(2);
    //$limitDate = now()->addDays(2);


    $penjadwalans = \App\Models\Penjadwalan::where('tipe', 'pengambilan')
        ->where('status', '!=', 'selesai')
        ->whereDate('tanggal', '<=', $limitDate)
        ->with('transaksi.detailTransaksis.produk')
        ->get();

    foreach ($penjadwalans as $jadwal) {
        $transaksi = $jadwal->transaksi;

        if ($transaksi && $transaksi->status !== 'selesai') {
            // Ubah status transaksi
            $transaksi->status = 'hangus';
            $transaksi->save();

            // Ubah status penjadwalan juga
            $jadwal->status = 'hangus';
            $jadwal->save();

            // Ubah status semua barang menjadi 'barang untuk donasi'
            foreach ($transaksi->detailTransaksis as $detail) {
                $barang = $detail->produk;
                if ($barang) {
                    $barang->status = 'barang untuk donasi';
                    $barang->save();
                }
            }

            \Log::info("Transaksi #{$transaksi->transaksiID} dan penjadwalan #{$jadwal->penjadwalanID} diubah menjadi hangus. Barang jadi donasi.");
        }
    }

    return Command::SUCCESS;
}

}
