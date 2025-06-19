<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProdukController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AlamatController;
use App\Http\Controllers\Api\MultiLoginController;
use App\Http\Controllers\Api\PenitipController;
use App\Http\Controllers\Api\PegawaiController;
use App\Http\Controllers\Api\OrganisasiController;
use App\Http\Controllers\Api\LupaResetPassController;
use App\Http\Controllers\Api\PembeliController;
use App\Http\Controllers\Api\DiskusiController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\Api\NotifikasiController;

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
Route::get('/produk/{id}', [ProdukController::class, 'show']);
Route::get('/diskusiProduk/{id}', [DiskusiController::class, 'diskusiProduk']);

// Route::get('/pembeli/{id}', [ProdukController::class, 'show']);
// Route::get('/pegawai/{id}', [PegawaiController::class, 'show']);
// Route::post('/register',[App\Http\Controllers\Api\AuthController::class,'register']);
// Route::post('/login',[App\Http\Controllers\Api\AuthController::class,'login']);
Route::post('/multi-login', [MultiLoginController::class, 'login']);
// Route::post('/multi-register', [MultiLoginController::class, 'register']);
Route::post('/multi-register', [MultiLoginController::class, 'register']);
Route::post('/forgot-password', [LupaResetPassController::class, 'forgotPassword']);
Route::post('/user-forgot-password', [LupaResetPassController::class, 'gantiPassword']);
// Route::post('/user-forgot-password', [LupaResetPassController::class, 'gantiPassword']);
// Route::post('/reset-password', [LupaResetPassController::class, 'resetPassword']);
Route::post('/pegawai/{id}/reset-password', [LupaResetPassController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function(){
    Route::get('/user', [App\Http\Controllers\Api\AuthController::class, 'user']);
    Route::put('/user/update', [AuthController::class, 'updateProfile']);
    Route::post('/alamat', [AlamatController::class, 'store']);
    Route::get('/alamat', [AlamatController::class, 'index']);
    Route::get('/alamat/{id}', [AlamatController::class, 'show']);
    Route::put('/alamat/{id}', [AlamatController::class, 'update']);
    Route::delete('/alamat/{id}', [AlamatController::class, 'destroy']);
    Route::post('/penitip', [PenitipController::class, 'store']);
    Route::get('/penitip', [PenitipController::class, 'index']); // show all
    Route::delete('/penitip/{id}', [PenitipController::class, 'destroy']); // delete
    Route::post('/pegawai', [PegawaiController::class, 'store']);
    Route::get('/pegawai', [PegawaiController::class, 'index']); // show all
    Route::delete('/pegawai/{id}', [PegawaiController::class, 'destroy']); // delete
    Route::put('/pegawai/{id}', [PegawaiController::class, 'update']);
    Route::get('/pegawai/{id}', [PegawaiController::class, 'show']);
    // routes/api.php
    Route::get('/diskusi/{produkID}', [DiskusiController::class, 'index']);
    Route::post('/diskusi', [DiskusiController::class, 'store']);

    // Route::post('/organisasi', [OrganisasiController::class, 'store']);
    Route::get('/organisasi', [OrganisasiController::class, 'index']); // show all
    Route::delete('/organisasi/{id}', [OrganisasiController::class, 'destroy']); // delete
    Route::put('/organisasi/{id}', [OrganisasiController::class, 'update']); // delete

    Route::get('/pembeli', [PembeliController::class, 'index']);
    Route::get('/pembeli/{id}', [PembeliController::class, 'show']);

    Route::get('/active-cart', [CartController::class, 'index']);
    Route::post('/active-cart/add', [CartController::class, 'addItem']);
    // Kirim idProduk sebagai parameter biasa
    Route::delete('/active-cart/item/{idProduk}', [CartController::class, 'removeItem']);
    Route::delete('/active-cart/clear', [CartController::class, 'clearCart']);

    // Mengambil daftar transaksi milik pengguna yang login
    // Route::get('/orders', [TransaksiController::class, 'index']);

    Route::get('/orders/pending-verification', [TransaksiController::class, 'getOrdersPendingVerification']);
    // Membuat transaksi/order baru
    Route::post('/orders', [TransaksiController::class, 'store']);

    // Menampilkan detail transaksi spesifik
    // {transaksi} akan menggunakan Route Model Binding ke model Transaksi Anda
    Route::get('/orders/{id}', [TransaksiController::class, 'show']);

    // Mengunggah bukti pembayaran untuk transaksi spesifik
    Route::post('/orders/{id}/payment-proof', [TransaksiController::class, 'uploadPaymentProof']);

    Route::post('/orders/{id}/verify-payment', [TransaksiController::class, 'verifyPayment']);

    Route::post('/orders/{transaksi}/approve-payment', [TransaksiController::class, 'approvePayment']);

    // Rute BARU untuk menolak pembayaran
    Route::post('/orders/{transaksi}/reject-payment', [TransaksiController::class, 'rejectPayment']);

    // Route::post('/api/notifications', [NotificationController::class, 'store']);
    Route::get('/notifications', [NotifikasiController::class, 'index']);
    Route::post('/notifications', [NotifikasiController::class, 'store']);
    Route::put('/notifications/{id}/read', [NotifikasiController::class, 'markAsRead']);
    Route::get('/users/{userId}/notifications', [NotificationController::class, 'getForUser']);


    });
