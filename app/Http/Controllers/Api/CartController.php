<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Produk; // Ganti Product menjadi Produk
use App\Models\Cart;
// use App\Models\Pembeli;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    // public function index(Request $request)
    // {
    //     $user = $request->user(); // Ini adalah instance App\Models\Pembeli

    //     // Validasi dasar untuk memastikan user adalah Pembeli dan memiliki relasi yang diperlukan
    //     if (!($user instanceof \App\Models\Pembeli) || !method_exists($user, 'activeCartItems')) {
    //         \Illuminate\Support\Facades\Log::error(
    //             'User tidak valid atau tidak ada metode activeCartItems di CartController@index',
    //             ['user_class' => $user ? get_class($user) : null, 'user_id' => $user ? $user->getKey() : null]
    //         );
    //         return response()->json(['message' => 'Akses tidak valid atau fungsi keranjang tidak tersedia untuk pengguna ini.'], 403);
    //     }

    //     // 1. Eager load relasi 'product' (singular) dari setiap item Cart
    //     // Pastikan nama relasi di model Cart Anda adalah 'product()'
    //     $cartItemsModels = $user->activeCartItems()->with('produkk')->get();

    //     // 2. Gunakan .map() untuk mengubah setiap item keranjang ke format yang diinginkan
    //     $formattedCartItems = $cartItemsModels->map(function ($cartItem) {
    //         // $cartItem adalah instance dari App\Models\Cart (atau model item keranjang Anda)

    //         // Periksa apakah produk terkait berhasil di-load
    //         if (!$cartItem->product) {
    //             // Jika produk tidak ditemukan (misalnya, sudah dihapus dari database),
    //             // kita bisa log error ini dan mengembalikan null agar item ini
    //             // tidak ikut dikirim ke frontend setelah difilter.
    //             \Illuminate\Support\Facades\Log::warning(
    //                 'Produk tidak ditemukan untuk item keranjang saat formatting.',
    //                 ['cart_item_id' => $cartItem->id, 'product_id_in_cart' => $cartItem->product_id]
    //             );
    //             return null; // Item ini akan dihilangkan oleh ->filter() nanti
    //         }

    //         // Gunakan accessor gambar_url jika Anda membuatnya di model Produk, atau path langsung
    //         // Pastikan path asset storage sudah benar jika gambar ada di storage/app/public
    //         $gambarUrl = $cartItem->product->gambar_url ?? ($cartItem->product->gambar ? asset('storage/images/produks/' . $cartItem->product->gambar) : asset('images/placeholder.jpg'));

    //         // Kembalikan array dengan struktur yang "rata"
    //         return [
    //             'id'             => $cartItem->id, // ID dari tabel user_active_cart_items (model Cart)
    //             'product_id'     => $cartItem->product->idProduk, // idProduk dari model Produk
    //             'name'           => $cartItem->product->namaProduk, // namaProduk dari model Produk
    //             'price'          => (float) $cartItem->price_at_add, // Harga saat ditambahkan
    //             'img'            => $gambarUrl,
    //             'stock_status'   => $cartItem->product->status, // Status dari model Produk
    //             'quantity'       => 1, // Selalu 1 untuk kasus barang bekas unik
    //             'added_at'       => $cartItem->created_at->toIso8601String(), // Format tanggal
    //             'deskripsi'      => $cartItem->product->deskripsi, // Dari model Produk
    //             'kategori'       => $cartItem->product->kategori,   // Dari model Produk
    //             // Tambahkan field lain yang Anda butuhkan dari $cartItem atau $cartItem->product
    //         ];
    //     })->filter() // ->filter() akan menghapus semua item yang bernilai null (yang produknya tidak ditemukan)
    //       ->values();  // ->values() akan mengatur ulang key array menjadi numerik [0, 1, 2,...] setelah filter

    //     return response()->json($formattedCartItems);
    // }

    public function index(Request $request)
{
    $user = $request->user();
    // Eager load relasi 'product' (singular) dari model Cart
    $cartItemsModels = $user->activeCartItems()->with('produkk')->get(); // <--- PASTIKAN INI 'product'

    return response()->json($cartItemsModels);
}

    public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:produks,idProduk', // Validasi ke produks.idProduk
        ]);

        $user = $request->user();
        // dd($user);
        $productId = $request->product_id; // Ini adalah idProduk

        $existingCartItem = Cart::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($existingCartItem) {
            $produk = Produk::find($productId);
            $gambarUrl = $produk->gambar_url ?? ($produk->gambar ? asset($produk->gambar) : asset('images/placeholder.jpg'));
            $formattedExistingItem = [
                'id' => $existingCartItem->id,
                'product_id' => $produk->idProduk,
                'name' => $produk->namaProduk,
                'price' => (float) $existingCartItem->price_at_add,
                'img' => $gambarUrl,
                'stock_status' => $produk->status,
                'quantity' => 1,
                'added_at' => $existingCartItem->created_at,
                'deskripsi' => $produk->deskripsi,
                'kategori' => $produk->kategori,
            ];
            return response()->json(['message' => 'Item is already in your cart.', 'cartItem' => $formattedExistingItem], 200);
        }

        return DB::transaction(function () use ($user, $productId) {
            // Gunakan model Produk
            $produk = Produk::where('idProduk', $productId)->lockForUpdate()->first();

            if (!$produk) {
                return response()->json(['message' => 'Product not found.'], 404);
            }

            // Gunakan kolom 'status' dari tabel produks Anda
            if ($produk->status === 'ada') { // Pastikan 'available' adalah nilai yang Anda gunakan
                $produk->status = 'in_cart'; // Pastikan 'in_cart' adalah nilai yang Anda gunakan
                $produk->cart_holder_user_id = $user->getKey();
                $produk->save();

                $cartItem = cart::create([
                    'user_id' => $user->getKey(),
                    'product_id' => $produk->idProduk, // Simpan idProduk
                    'price_at_add' => $produk->harga,  // Ambil dari kolom harga
                ]);
                
                $gambarUrl = $produk->gambar_url ?? ($produk->gambar ? asset($produk->gambar) : asset('images/placeholder.jpg'));
                $formattedCartItem = [
                    'id' => $cartItem->id,
                    'product_id' => $produk->idProduk,
                    'name' => $produk->namaProduk,
                    'price' => (float) $cartItem->price_at_add,
                    'img' => $gambarUrl,
                    'stock_status' => $produk->status,
                    'quantity' => 1,
                    'added_at' => $cartItem->created_at,
                    'deskripsi' => $produk->deskripsi,
                    'kategori' => $produk->kategori,
                ];
                return response()->json([
                    'message' => 'Item added to cart.',
                    'cartItem' => $formattedCartItem
                ], 201);

            } elseif ($produk->status === 'in_cart' && $produk->cart_holder_user_id == $user->id) {
                return response()->json(['message' => 'Item is already in your cart.'], 409);
            } elseif ($produk->status === 'in_cart') {
                return response()->json(['message' => 'Item is in someone else\'s cart.'], 409);
            } elseif ($produk->status === 'sold') { // Pastikan 'sold' adalah nilai yang Anda gunakan
                return response()->json(['message' => 'Item has already been sold.'], 409);
            }
            // Sesuaikan nilai status ('available', 'in_cart', 'sold') dengan yang Anda gunakan di database.
            return response()->json(['message' => 'Failed to add item to cart. Product status: ' . $produk->status], 500);
        });
    }

    // Gunakan $idProduk sebagai parameter yang diterima dari route
    public function removeItem(Request $request, $idProduk)
    {
        $user = $request->user();
        // Cari produk berdasarkan idProduk
        $produk = Produk::where('idProduk', $idProduk)->first();

        if (!$produk) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return DB::transaction(function () use ($user, $produk) {
            $cartItem = cart::where('user_id', $user->getKey())
                ->where('product_id', $produk->idProduk) // Cocokkan dengan idProduk
                ->first();

            if ($cartItem) {
                $cartItem->delete();

                if ($produk->status === 'in_cart' && $produk->cart_holder_user_id === $user->id) {
                    $produk->status = 'available'; // Kembalikan ke status available
                    $produk->cart_holder_user_id = null;
                    $produk->save();
                }
                return response()->json(['message' => 'Item removed from cart.']);
            }
            return response()->json(['message' => 'Item not found in your cart.'], 404);
        });
    }

    public function clearCart(Request $request)
    {
        $user = $request->user();
        return DB::transaction(function () use ($user) {
            $cartItems = $user->Cart()->with('product')->get();
            foreach ($cartItems as $item) {
                if ($item->product && $item->product->status === 'in_cart' && $item->product->cart_holder_user_id === $user->id) {
                    $item->product->status = 'ada';
                    $item->product->cart_holder_user_id = null;
                    $item->product->save();
                }
                $item->delete();
            }
            return response()->json(['message' => 'Cart cleared successfully.']);
        });
    }
}