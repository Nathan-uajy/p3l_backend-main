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
use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\Api\DetailTransaksiController;
use App\Http\Controllers\Api\PenjadwalanController;
use App\Http\Controllers\Api\NotaKurirController;
use App\Http\Controllers\Api\NotaPembeliController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\FCMController;
use App\Http\Controllers\Api\MerchandiseController;
use App\Http\Controllers\Api\LaporanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/produk', [ProdukController::class, 'index']);
//Route::post('/register',[App\Http\Controllers\Api\AuthController::class,'register']);
//Route::post('/login',[App\Http\Controllers\Api\AuthController::class,'login']);
Route::post('/multi-login', [MultiLoginController::class, 'login']);
Route::post('/multi-register', [MultiLoginController::class, 'register']);
Route::get('/produk', [ProdukController::class, 'index']);
Route::post('/produk', [ProdukController::class,'store']);



//MOBILE
    Route::get('/barang/available', [BarangController::class, 'available']);
    Route::get('/barang-mobile/{id}', [BarangController::class, 'showDetailMobile']);
    Route::post('/klaim-merchandise', [MerchandiseController::class, 'klaimMerchandise']);
    Route::get('/merchandise', [MerchandiseController::class, 'index']);

Route::middleware('auth:sanctum')->group(function(){
    Route::get('/user', [App\Http\Controllers\Api\AuthController::class, 'user']);
    Route::put('/user/update', [AuthController::class, 'updateProfile']);
    
    Route::post('/alamat', [AlamatController::class, 'store']);
    Route::get('/alamat', [AlamatController::class, 'index']);
    Route::get('/alamat/{id}', [AlamatController::class, 'show']);
    Route::put('/alamat/{id}', [AlamatController::class, 'update']);
    Route::delete('/alamat/{id}', [AlamatController::class, 'destroy']);

    Route::post('/penitip', [PenitipController::class, 'store']);
    Route::get('/penitip', [PenitipController::class, 'index']);
    Route::delete('/penitip/{id}', [PenitipController::class, 'destroy']);
    Route::get('/penitip/search', [PenitipController::class, 'search']);
    Route::put('/penitip/{id}', [PenitipController::class, 'update']);

    Route::get('/request-donasi', [RequestDonasiController::class, 'index']);
    Route::post('/request-donasi', [RequestDonasiController::class, 'store']);
    Route::put('/request-donasi/{id}', [RequestDonasiController::class, 'update']);
    Route::delete('/request-donasi/{id}', [RequestDonasiController::class, 'destroy']);

    Route::get('/donasi', [DonasiController::class, 'index']);
    Route::post('/donasi', [DonasiController::class, 'store']);
    
    Route::get('/pegawai', [PegawaiController::class, 'index']);
    Route::post('/pegawai', [PegawaiController::class, 'store']);
    Route::put('/pegawai/{id}', [PegawaiController::class, 'update']);
    Route::delete('/pegawai/{id}', [PegawaiController::class, 'destroy']);
    Route::get('/kurirs', [PegawaiController::class, 'getKurirs']);
    Route::get('/gudangs', [PegawaiController::class, 'getGudangs']);

    Route::get('/barang-penitip/{id}', [BarangController::class, 'getByPenitip']);
    Route::get('/barang/{id}', [BarangController::class, 'show']);
    Route::get('/barang-search', [BarangController::class, 'search']);
    Route::put('/barang-perpanjang/{id}', [BarangController::class, 'perpanjang']);
    Route::post('/barang', [BarangController::class, 'store']);
    Route::put('/barang-konfirmasi-ambil/{id}', [BarangController::class, 'konfirmasiAmbil']);
    Route::put('/barang-donasikan/{id}', [BarangController::class, 'donasikan']);
    Route::put('/barang-diambil/{id}', [BarangController::class, 'markAsTaken']);
    Route::get('/barang-menunggu-diambil', [BarangController::class, 'semuaMenungguDiambil']);
    Route::put('/barang-diterima/{id}', [BarangController::class, 'tandaiDiambil']);
    Route::get('/gudang-barang-diambil', [BarangController::class, 'gudangBarangDiambil']);
    Route::get('/barang-diambil/{id}', [BarangController::class, 'showDetail']);

    Route::apiResource('/transaksis', TransaksiController::class);
    Route::apiResource('/detail-transaksis', DetailTransaksiController::class);
    Route::get('/uji-komisi/{id}', [TransaksiController::class, 'simpanKomisi']);
    Route::put('/transaksis/{id}/status', [App\Http\Controllers\Api\TransaksiController::class, 'updateStatusTransaksi']);


    Route::get('/gudang-transaksis', [TransaksiController::class, 'index']);
    Route::get('/transaksi-gudang', [TransaksiController::class, 'transaksiGudang']);
    Route::get('/index-gudang', [TransaksiController::class, 'indexGudang']);
    Route::put('/transaksi/{id}/status', [TransaksiController::class, 'updateStatus']);

    Route::post('/penjadwalans', [PenjadwalanController::class, 'store']);
    Route::get('/penjadwalans', [PenjadwalanController::class, 'index']);
    Route::put('/penjadwalans/{id}/update-status', [PenjadwalanController::class, 'updateStatus']);
    Route::put('/penjadwalans/{id}/konfirmasi-selesai', [PenjadwalanController::class, 'konfirmasiSelesai']);
    Route::put('/penjadwalans/{id}/konfirmasi-diterima', [PenjadwalanController::class, 'konfirmasiDiterima']);
    Route::get('/kurir/tugas', [PenjadwalanController::class, 'getTugas']);
    

    Route::get('/transaksi-pengambilan', [TransaksiController::class, 'transaksiPengambilan']);
    Route::get('/gudang-transaksis-ambil', [TransaksiController::class, 'transaksiGudangAmbil']);

    Route::get('/nota-kurir-data/{transaksiID}', [NotaKurirController::class, 'getNotaData']);
    Route::get('/nota-kurir-daftar', [NotaKurirController::class, 'daftarTransaksiSiap']);

    Route::get('/nota-pembeli-daftar', [NotaPembeliController::class, 'daftarTransaksi']);
    Route::get('/nota-pembeli-data/{id}', [NotaPembeliController::class, 'getNotaData']);

    Route::post('/kirim-notifikasi', [NotifikasiController::class, 'kirimNotifikasi']);
    Route::post('/simpan-fcm-token', [FCMController::class, 'simpanToken']);
    Route::post('/kirim-notifikasi-uji', [NotifikasiController::class, 'kirimNotifUji']);
    Route::post('/hapus-fcm-token', [FCMController::class, 'hapusToken']);
    Route::post('/logout', [MultiLoginController::class, 'logout'])->middleware('auth:api');

    Route::get('/penitip-saldo-besar', [PenitipController::class, 'penitipSaldoBesar']);

    // Tambahkan route ini
    Route::get('/laporan-donasi-barang', [LaporanController::class, 'laporanDonasiBarang']);
    // Tambahkan route ini di dalam grup middleware auth:sanctum
    Route::get('/laporan-request-donasi', [LaporanController::class, 'laporanRequestDonasi']);
    Route::get('/laporan/transaksi-penitip', [LaporanController::class, 'laporanTransaksiPenitip']);

    








});
