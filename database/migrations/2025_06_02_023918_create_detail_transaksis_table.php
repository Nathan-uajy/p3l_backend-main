<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_transaksis', function (Blueprint $table) {
            $table->id('id_detail_transaksi'); // Sesuai gambar
            $table->unsignedBigInteger('transaksiID'); // FK ke transaksis.idTransaksi
            $table->unsignedBigInteger('produkID');    // FK ke produks.idProduk

            $table->foreign('transaksiID')->references('idTransaksi')->on('transaksis')->onDelete('cascade');
            $table->foreign('produkID')->references('idProduk')->on('produks')->onDelete('cascade'); // atau restrict/set null
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_transaksis');
    }
};