<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('detail_transaksis', function (Blueprint $table) {
            $table->id('id_detail_transaksi');
            $table->unsignedBigInteger('transaksiID');
            $table->unsignedBigInteger('produkID');
            $table->timestamps();

            // Perhatikan referensi foreign key harus cocok dengan kolom di tabel aslinya
            $table->foreign('transaksiID')->references('transaksiID')->on('transaksis')->onDelete('cascade');
            $table->foreign('produkID')->references('idProduk')->on('barangs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_transaksis');
    }
};
