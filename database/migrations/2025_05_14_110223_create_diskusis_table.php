<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('diskusis', function (Blueprint $table) {
            $table->id('diskusiID');
            $table->text('isi');
            $table->dateTime('tanggal');

            // Relasi opsional: pembeli / pegawai
            $table->unsignedBigInteger('pembeliID')->nullable();
            $table->unsignedBigInteger('pegawaiID')->nullable();
            $table->unsignedBigInteger('produkID');

            // Foreign key (opsional, tergantung apakah tabel produk/pembeli/pegawai sudah ada)
            $table->foreign('pembeliID')->references('pembeliID')->on('pembelis')->onDelete('set null');
            $table->foreign('pegawaiID')->references('pegawaiID')->on('pegawais')->onDelete('set null');
            $table->foreign('produkID')->references('idProduk')->on('produks')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diskusis');
    }
};
