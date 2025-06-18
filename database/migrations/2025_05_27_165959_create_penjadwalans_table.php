<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('penjadwalans', function (Blueprint $table) {
            $table->bigIncrements('penjadwalanID');
            $table->unsignedBigInteger('transaksiID');
            $table->unsignedBigInteger('pegawaiID')->nullable(); // null = pengambilan
            $table->enum('tipe', ['pengiriman', 'pengambilan']);
            $table->enum('status', [
                'diproses', 
                'siap dikirim', 
                'siap diambil', 
                'berhasil dikirim', 
                'berhasil diambil'
            ]);
            $table->date('tanggal');
            $table->time('waktu');
            $table->timestamps();

            $table->foreign('transaksiID')->references('transaksiID')->on('transaksis')->onDelete('cascade');
            $table->foreign('pegawaiID')->references('pegawaiID')->on('pegawais')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjadwalans');
    }
};
