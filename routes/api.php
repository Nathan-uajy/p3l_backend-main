<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProdukController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AlamatController;
use App\Http\Controllers\Api\MultiLoginController;
use App\Http\Controllers\Api\PenitipController;
use App\Http\Controllers\Api\RequestDonasiController;
use App\Http\Controllers\Api\DonasiController;
use App\Http\Controllers\Api\PegawaiController;
use App\Http\Controllers\Api\BarangController;
use App\Http\Controllers\Api\DetailTransaksiController;
use App\Http\Controllers\Api\PenjadwalanController;
use App\Http\Controllers\Api\GudangController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\Api\MerchandiseController;
use App\Http\Controllers\Api\TransaksiMerchandiseController;
use App\Http\Controllers\Api\PembeliController;
use App\Http\Controllers\Api\TransaksiController;

Route::post('/multi-login', [MultiLoginController::class, 'login']);
Route::post('/multi-register', [MultiLoginController::class, 'register']);
Route::get('/produk', [ProdukController::class, 'index']);
Route::post('/produk', [ProdukController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/update', [AuthController::class, 'updateProfile']);

    Route::apiResource('alamat', AlamatController::class);
    // Route::apiResource('penitip', PenitipController::class);
    Route::get('/penitips', [PenitipController::class, 'index']);
    Route::get('/penitip/search', [PenitipController::class, 'search']);
    Route::get('/penitip/list-with-sales', [PenitipController::class, 'listWithSales']);
    Route::post('/penitip/{penitipID}/set-top-seller', [PenitipController::class, 'setTopSeller']);
    Route::get('/barang-penitip/{penitipID}', [PenitipController::class, 'getBarangDititipkan']);

    Route::apiResource('request-donasi', RequestDonasiController::class);
    Route::apiResource('donasi', DonasiController::class);
    Route::apiResource('pegawai', PegawaiController::class);
    Route::get('pegawai/me', [PegawaiController::class, 'me']);
    Route::get('/kurirs', [PegawaiController::class, 'getKurirs']);

    Route::get('/pembeli/profile', [PembeliController::class, 'profile']);
    Route::put('/pembeli/profile', [PembeliController::class, 'updateProfile']);
    Route::get('/pembeli/transactions', [TransaksiController::class, 'buyerHistory']);
    Route::get('/penitip/profile', [PenitipController::class, 'profile']);
    
    // Routes for BarangController (specific to penitip-related operations)
    Route::get('/barang-penitip/{id}', [BarangController::class, 'getByPenitip']);
    Route::get('/barang/search-by-penitip', [BarangController::class, 'search']);
    Route::put('/barang-perpanjang/{id}', [BarangController::class, 'perpanjang']);
    Route::put('/barang/konfirmasi-ambil/{id}', [BarangController::class, 'konfirmasiAmbil']);
    Route::put('/barang/donasikan/{id}', [BarangController::class, 'donasikan']);
    Route::put('/barang/mark-as-taken/{id}', [BarangController::class, 'markAsTaken']);
    Route::get('/barang/semua-menunggu-diambil', [BarangController::class, 'semuaMenungguDiambil']);
    Route::put('/barang-diterima/{id}', [BarangController::class, 'tandaiDiambil']);
    Route::get('/gudang-barang-diambil', [BarangController::class, 'gudangBarangDiambil']);
    Route::post('/barang/check-expired-and-donate', [BarangController::class, 'checkExpiredAndDonate']);
    Route::get('/barang/monthly-sales', [BarangController::class, 'getMonthlySales']);
    Route::get('/barang/monthly-commissions', [BarangController::class, 'getMonthlyCommissions']);
    Route::get('/barang/gudang-stok', [BarangController::class, 'gudangStok']);

    // Routes for GudangController (warehouse operations)
    Route::get('/gudang', [GudangController::class, 'index']);
    Route::post('/gudang', [GudangController::class, 'store']);
    Route::get('/gudang/{id}', [GudangController::class, 'show']);
    Route::put('/gudang/{id}', [GudangController::class, 'update']);
    Route::delete('/gudang/{id}', [GudangController::class, 'destroy']);
    Route::get('/gudang/search', [GudangController::class, 'search']);
    Route::get('/kategori', [KategoriController::class, 'index']);
    // Transaksi and Detail Transaksi
    Route::apiResource('transaksis', TransaksiController::class);
    Route::apiResource('detail-transaksis', DetailTransaksiController::class);
    Route::get('/gudang-transaksis', [TransaksiController::class, 'index']);

    // Penjadwalan
    Route::post('/penjadwalans', [PenjadwalanController::class, 'store']);
    Route::get('/penjadwalans', [PenjadwalanController::class, 'index']);
    Route::put('/penjadwalans/{id}/update-status', [PenjadwalanController::class, 'updateStatus']);
    Route::put('/penjadwalans/{id}/konfirmasi-selesai', [PenjadwalanController::class, 'konfirmasiSelesai']);
    Route::put('/penjadwalans/{id}/konfirmasi-diterima', [PenjadwalanController::class, 'konfirmasiDiterima']);

    Route::get('/transaksimerchandise', [TransaksiMerchandiseController::class, 'index']);
    Route::post('/transaksimerchandise', [TransaksiMerchandiseController::class, 'store']);
    Route::put('/transaksimerchandise/updateTanggalAmbil/{id}', [TransaksiMerchandiseController::class, 'updateTanggalAmbil']);
    Route::delete('/transaksimerchandise/{id}', [TransaksiMerchandiseController::class, 'destroy']);

    Route::get('/merchandise', [MerchandiseController::class, 'index']);
    Route::post('/merchandise/klaim', [MerchandiseController::class, 'klaimMerchandise']);

    

});