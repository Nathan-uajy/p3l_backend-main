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
    public function up()
    {
        // Menggunakan nama tabel 'donasi' (bentuk tunggal)
        Schema::create('donasi', function (Blueprint $table) {
            $table->id('donasiID');
            
            // Foreign key ke produk yang didonasikan
            $table->foreignId('produkID')->constrained('barangs', 'idProduk')->onDelete('cascade');
            
            // Foreign key ke organisasi penerima
            $table->foreignId('organisasiID')->constrained('organisasis', 'organisasiID')->onDelete('cascade');
            
            // PERBAIKAN: Kolom ini sekarang hanya menyimpan nama penerima sebagai teks biasa.
            $table->string('namaPenerima');

            $table->date('tanggal_donasi');
            $table->integer('jumlah')->default(1);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Menggunakan nama tabel 'donasi'
        Schema::dropIfExists('donasi');
    }
};
