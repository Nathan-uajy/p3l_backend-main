<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Pembeli;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\Barang;
use App\Models\Penjadwalan;
use Illuminate\Support\Facades\Validator;
use App\Models\Komisi;
use Carbon\Carbon;
use App\Models\Cart;
use App\Models\DetailTransaksi;
use App\Models\Pegawai;



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
                    $produk = Barang::where('idProduk', $cartItem->product_id)
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

    public function rejectPayment(Request $request, Transaksi $transaksi)
    {
        // $pembeli = $transaksi->pembeli;
        
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
