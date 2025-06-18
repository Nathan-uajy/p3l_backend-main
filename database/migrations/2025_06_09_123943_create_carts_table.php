<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('cart', function (Blueprint $table) {
            $table->id(); // Primary key auto-increment untuk tabel 'cart'

            // Foreign key untuk ID Pembeli
            // Kolom ini akan menyimpan 'pembeliID' dari tabel 'pembelis'
            $table->unsignedBigInteger('user_id'); // Mengasumsikan 'pembeliID' adalah tipe BIGINT UNSIGNED
                                                  // Jika 'pembeliID' adalah INTEGER UNSIGNED, gunakan $table->unsignedInteger('user_id');

            // Foreign key untuk ID Produk
            // Kolom ini akan menyimpan 'idProduk' dari tabel 'produks'
            $table->unsignedBigInteger('product_id'); // 'idProduk' adalah bigIncrements (BIGINT UNSIGNED)

            $table->decimal('price_at_add', 15, 2); // Harga produk pada saat ditambahkan ke keranjang
            $table->timestamps(); // Membuat kolom created_at dan updated_at

            // Definisi Foreign Key Constraints
            // Merujuk ke kolom 'pembeliID' di tabel 'pembelis'
            $table->foreign('user_id')
                  ->references('pembeliID') // Nama primary key di tabel 'pembelis'
                  ->on('pembelis')          // Nama tabel 'pembelis'
                  ->onDelete('cascade');     // Jika pembeli dihapus, item keranjangnya juga dihapus

            // Merujuk ke kolom 'idProduk' di tabel 'produks'
            $table->foreign('product_id')
                  ->references('idProduk')  // Nama primary key di tabel 'produks'
                  ->on('barangs')           // Nama tabel 'produks'
                  ->onDelete('cascade');     // Jika produk dihapus, item keranjangnya juga dihapus

            $table->unique(['user_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cart');
    }
};