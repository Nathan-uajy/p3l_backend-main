<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Pegawai; // Diasumsikan model Pembeli/Pelanggan Anda
use App\Models\Produk;
use App\Models\Cart;    // Model untuk user_active_cart_items
use App\Models\Notification; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule; // Untuk validasi yang lebih kompleks jika perlu

class TransaksiController extends Controller
{
    /**
     * Menampilkan daftar transaksi untuk pengguna yang terautentikasi.
     */
    // Di Controller Backend
public function index(Request $request)
{
    // Bisa untuk admin melihat semua, atau pembeli melihat miliknya (tambahkan filter pegawaiID jika untuk pembeli)
    $query = Transaksi::query();

    if ($request->has('status') && $request->status != '') {
        // Tambahkan validasi untuk $request->status di sini
        $query->where('status', 'waiting_validation');
    }

    // Tambahkan eager loading, order, paginasi sesuai kebutuhan
    $transaksis = $query->with('pembeli')->orderBy('tanggalTransaksi', 'desc')->paginate(10);

    return response()->json($transaksis);
}

    /**
     * Membuat transaksi baru.
     * Data untuk membuat transaksi ini berasal dari 'orderPayload' yang dikirim frontend.
     */
    public function store(Request $request)
    {
        $pembeli = $request->user(); // Ini adalah instance Pegawai (Pembeli)

        $validatedData = $request->validate([
            'shipping_method' => ['required', Rule::in(['kurir', 'ambil_sendiri'])],
            'address_id' => [
                'required_if:shipping_method,kurir',
                'nullable',
            ],
            'penitipID' => 'required|integer|exists:penitips,penitipID',
            'selected_bank_name' => 'nullable|string|max:255', // Untuk metodePembayaran
            'penjadwalanID' => 'nullable|integer', // Tambahkan 'exists:nama_tabel_penjadwalan,id' jika perlu validasi
            'diskon' => 'nullable|numeric|min:0',
            'poin_ingin_ditukar' => 'nullable|integer|min:0'
        ]);

        $activeCartItems = $pembeli->activeCartItems()->with('produkk')->get();

        if ($activeCartItems->isEmpty()) {
            return response()->json(['message' => 'Keranjang Anda kosong.'], 400);
        }
        
        try {
            // Menggunakan DB::transaction untuk memastikan semua operasi database berhasil atau di-rollback semua
            return DB::transaction(function () use ($request, $pembeli, $activeCartItems, $validatedData) {
                $subtotalProduk = 0;
                $itemsToUpdateStatus = []; // Menyimpan instance produk yang perlu diupdate statusnya

                foreach ($activeCartItems as $cartItem) {
                    $produk = Produk::where('idProduk', $cartItem->product_id)
                                    ->lockForUpdate() // Kunci baris produk untuk mencegah race condition
                                    ->first();

                    if (!$produk || !($produk->status === 'ada' || ($produk->status === 'in_cart' && $produk->cart_holder_user_id === $pembeli->getKey()))) {
                        $errorMessage = 'Produk ' . ($produk->namaProduk ?? 'ID:'.$cartItem->product_id) . ' tidak lagi tersedia atau ada masalah. Harap perbarui keranjang Anda.';
                        Log::warning($errorMessage, ['user_id' => $pembeli->getKey(), 'produk_id' => $cartItem->product_id, 'status_produk_db' => $produk->status ?? 'Tidak ditemukan']);
                        // Melemparkan exception akan otomatis rollback transaksi
                        throw new \Exception($errorMessage);
                    }
                    $subtotalProduk += $cartItem->price_at_add; // Harga dari keranjang (price_at_add)
                    $itemsToUpdateStatus[] = $produk; // Kumpulkan instance produk untuk diupdate nanti
                }

                $ongkir = 0;
                if ($validatedData['shipping_method'] === 'kurir') {
                    $ongkir = ($subtotalProduk >= 1500000) ? 0 : 100000;
                }

                $diskon = (float) ($validatedData['diskon'] ?? $request->input('diskon', 0));
                $totalHarga = $subtotalProduk + $ongkir - $diskon;

                $poinYangDitukarFinal = 0;
                $nilaiDiskonDariPoin = 0;
                $poinInginDitukarDariRequest = (int) ($validatedData['poin_ingin_ditukar'] ?? $request->input('poin_ingin_ditukar', 0));
                $poinLoyalitasAwalPembeli = $pembeli->poinLoyalitas ?? 0;

                if ($poinInginDitukarDariRequest > 0) {
                    if ($poinLoyalitasAwalPembeli >= $poinInginDitukarDariRequest) {
                        $maxNilaiDiskon = $subtotalProduk;
                        $nilaiDiskonPotensial = $poinInginDitukarDariRequest * 10000;
                        $poinYangDitukarFinal = ($nilaiDiskonPotensial > $maxNilaiDiskon) ? floor($maxNilaiDiskon / 10000) : $poinInginDitukarDariRequest;
                        $nilaiDiskonDariPoin = $poinYangDitukarFinal * 10000;

                        $pembeli->poinLoyalitas -= $poinYangDitukarFinal;

                        // if ($poinYangDitukarFinal > 0) {
                        //     $pembeli->poinLoyalitas = $poinLoyalitasAwalPembeli - $poinYangDitukarFinal;
                        //     Log::info("Poin loyalitas untuk pembeli ID {$pembeli->getKey()} akan dikurangi. Poin awal: {$poinLoyalitasAwalPembeli}, Ditukar: {$poinYangDitukarFinal}, Sisa: {$pembeli->poinLoyalitas}");
                        // }
                    } else {
                        Log::warning("User {$pembeli->getKey()} poin tidak cukup ({$poinLoyalitasAwalPembeli}) untuk menukar {$poinInginDitukarDariRequest} poin.");
                    }
                }

                // Kolom 'diskon' di tabel transaksis akan menyimpan nilaiDiskonDariPoin
                $diskonLainDariRequest = (float) ($validatedData['diskon'] ?? $request->input('diskon', 0));
                $diskonFinal = $nilaiDiskonDariPoin + $diskonLainDariRequest;// Jika ada diskon lain
                $grandTotalBackend = $subtotalProduk + $ongkir - $diskonFinal;


                // Data untuk membuat record di tabel 'transaksis'
                // Sesuai dengan $fillable di model Transaksi yang ketat mengikuti gambar Anda
                $transaksiData = [
                    'pembeliID'        => $pembeli->getKey(),
                    // 'penjadwalanID'    => $validatedData['penjadwalanID'] ?? $request->input('penjadwalanID'),
                    'penitipID'        => $validatedData['penitipID'],
                    'alamatID'         => $validatedData['shipping_method'] === 'kurir' ? ($validatedData['address_id'] ?? null) : null,
                    'totalHarga'       => $grandTotalBackend,
                    'status'           => 'pending_payment', // Status awal setelah order dibuat
                    'tanggalTransaksi' => now()->toDateString(), // Hanya tanggal
                    'metodePembayaran' => $validatedData['selected_bank_name'] ?? $request->input('metodePembayaran', 'Transfer Bank'),
                    'biayaPengiriman'  => $ongkir,
                    'diskon'           => $diskonFinal,
                    'poin_ditukar'     => $poinYangDitukarFinal, 
                    'subtotal_produk'  => $subtotalProduk,
                    'nomor_transaksi_unik' => 'INV-' . date('YmdHis') . '-' . strtoupper(Str::random(4)),
                    // 'buktiPembayaran' akan diisi nanti saat upload
                ];
                $transaksi = Transaksi::create($transaksiData);

                // Buat entri di tabel 'detail_transaksis' untuk setiap item
                foreach ($activeCartItems as $cartItem) {
                    DetailTransaksi::create([
                        'transaksiID' => $transaksi->idTransaksi,
                        'produkID'    => $cartItem->product_id, // Hanya ID produk
                    ]);
                }

                // Update status semua produk yang diproses menjadi 'sold'
                foreach ($itemsToUpdateStatus as $produkToUpdate) {
                    $produkToUpdate->status = 'sold';
                    $produkToUpdate->cart_holder_user_id = null;
                    $produkToUpdate->save();
                }

                // if ($pembeli && $pembeli->isDirty('poinLoyalitas')) {
                //     $pembeli->save();
                // }

                // if ($pembeli->isDirty('poinLoyalitas')) {
                //     $pembeli->save();
                //     Log::info("Poin loyalitas pembeli ID {$pembeli->getKey()} BERHASIL DISIMPAN. Poin sekarang: {$pembeli->poinLoyalitas}");
                // }
                $pembeli->save();

                // Kosongkan keranjang aktif pengguna
                Cart::where('user_id', $pembeli->getKey())->delete();

                // $transaksi->load(['items.produk', 'pegawai']); // Muat relasi untuk respons
                $transaksi->load(['detailTransaksis.produk', 'pembeli', 'penitip']); 

                return response()->json([
                    'message' => 'Pesanan berhasil dibuat. Silakan lanjutkan ke pembayaran.',
                    'order' => $transaksi
                ], 201);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            // DB::rollBack() tidak diperlukan di sini karena transaction akan otomatis rollback jika exception keluar dari closure
            Log::error('TransaksiController@store: Kesalahan validasi.', ['errors' => $e->errors(), 'uid' => $pembeli->getKey()]);
            return response()->json(['message' => 'Data yang dikirim tidak valid.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::critical('TransaksiController@store: Exception saat membuat pesanan.', ['uid' => $pembeli->getKey(), 'msg' => $e->getMessage(), 'req' => $request->all(), 'trace' => substr($e->getTraceAsString(),0,1000)]);
            return response()->json(['message' => $e->getMessage() ?: 'Terjadi kesalahan internal saat memproses pesanan Anda.'], 500);
        }
    }


    public function show(Request $request, $idTransaksi)
    {
        // $detailtransaksi->load(['transaksi']);
        $transaksi = Transaksi::find($idTransaksi);
        return response()->json($transaksi);
    }

    /**
     * Mengunggah bukti pembayaran.
     */
    public function uploadPaymentProof(Request $request,  $idTransaksi)
    {
        $transaksi = Transaksi::find($idTransaksi);

        $request->validate([
            'buktiPembayaran' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('buktiPembayaran')) {
            $file = $request->file('buktiPembayaran');
            $fileName = 'bukti_' . $transaksi->idTransaksi . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Simpan file dan dapatkan path relatif dari disk 'public'
            $filePath = $file->storeAs('buktiPembayaran', $fileName, 'public');

            // Hapus bukti lama jika ada (menggunakan path relatif)
            if ($transaksi->buktiPembayaran) {
                Storage::disk('public')->delete($transaksi->buktiPembayaran);
            }

            $transaksi->buktiPembayaran = $filePath; // Simpan path relatif
            $transaksi->status = 'awaiting_verification';
            $transaksi->save();

            // $transaksi->load(['items.produk']);
            return response()->json([
                'message' => 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi.',
                'order' => $transaksi
            ]);
        }
        return response()->json(['message' => 'File bukti pembayaran tidak ditemukan.'], 400);
    }

    //

    public function getOrdersPendingVerification(Request $request)
    {
        $orders = Transaksi::where('status','awaiting_verification')->get();
        return response()->json($orders);
    }

    public function approvePayment(Request $request, Transaksi $transaksi)
    {
        $pembeli = $transaksi->pembeli;
        DB::beginTransaction();
        try {
            $transaksi->status = 'disiapkan';
            $jumlahPembelianUntukPoin = $transaksi->subtotal_produk ?? 0;
            $penitip_id = $transaksi->penitipID;

            if ($jumlahPembelianUntukPoin > 0) {
                $poinDasar = floor($jumlahPembelianUntukPoin / 10000); // 1 poin = Rp 10.000
                $totalPoinDiperoleh = $poinDasar;

                if ($jumlahPembelianUntukPoin > 500000) { // Bonus 20%
                    $totalPoinDiperoleh += floor($poinDasar * 0.20);
                }
                if ($totalPoinDiperoleh > 0) {
                    // $pembeli->poinLoyalitas = ($pembeli->poinLoyalitas ?? 0) + $totalPoinDiperoleh;
                    // $pembeli->save(); // Akan di-save di akhir bersama transaksi
                    $pembeli->increment('poinLoyalitas', $totalPoinDiperoleh);
                }
            }
            $transaksi->save();

            if ($transaksi->status === 'disiapkan') {
                // Eager load items beserta produk dan penitip dari produk tersebut
                // untuk efisiensi jika belum di-load sebelumnya.
                $transaksi->loadMissing('items.produk.penitip');

                foreach ($transaksi->items as $detailItem) {
                    // Pastikan produk ada, penitip ada pada produk tersebut, dan email penitip ada
                    if ($detailItem->produk && $detailItem->produk->penitip && $detailItem->produk->penitip->email) {
                        
                        $penitipUntukNotif = $detailItem->produk->penitip;
                        $produkNotif = $detailItem->produk; // Produk spesifik yang terjual ini

                        try {
                            // Mengirim email secara langsung (bukan via queue untuk contoh sederhana ini)
                            // Mailable NotifyPenitipOrderPrepared akan mengambil data dari $transaksi, $produkNotif, $penitipUntukNotif
                            // untuk kemudian digunakan di dalam metode content() dan diteruskan ke view email.
                            
                            Log::info("Email notifikasi berhasil dikirim ke penitip {$penitipUntukNotif->email} untuk produk '{$produkNotif->namaProduk}' dalam Transaksi ID {$transaksi->idTransaksi}.");

                        } catch (\Exception $mailException) {
                            Log::error("GAGAL mengirim email notifikasi ke penitip {$penitipUntukNotif->email} untuk TrxID {$transaksi->idTransaksi}, ProdukID {$produkNotif->idProduk}: " . $mailException->getMessage());
                            // Pertimbangkan: apakah kegagalan kirim email harus rollback transaksi?
                            // Untuk tugas, mungkin tidak perlu, cukup log error.
                        }
                    } else {
                        Log::warning("Data produk, penitip, atau email penitip tidak lengkap untuk item detail ID {$detailItem->id_detail_transaksi} di TrxID {$transaksi->idTransaksi}. Notifikasi tidak dikirim.");
                    }
                }
            }

            DB::commit();

            // Setelah pembayaran berhasil
            // Notification::create([
            //     'user_id' => $penitip_id,
            //     'title' => 'Barang Laku!',
            //     'message' => 'Barang kamu laku, dan pembayaran sudah diverifikasi.',
            // ]);

            return response()->json(['message' => 'Pembayaran berhasil disetujui. Status transaksi: Disiapkan.', 'order' => $transaksi]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menyetujui pembayaran.'], 500);
        }
    }

    /**
     * Menolak pembayaran dan mengubah status menjadi "Payment Failed".
     */
    public function rejectPayment(Request $request, Transaksi $transaksi)
    {
        DB::beginTransaction();
        try {
            $transaksi->status = 'payment_failed';
            // if ($pembeli && $transaksi->poin_ditukar > 0) {
            //     $pembeli->poinLoyalitas = ($pembeli->poinLoyalitas ?? 0) + $transaksi->poin_ditukar;
            //     // $pembeli->save();
            // }
            $transaksi->save();

            return response()->json(['message' => 'Pembayaran ditolak. Status transaksi telah diupdate.', 'order' => $transaksi]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menolak pembayaran.'], 500);
        }
    }
}