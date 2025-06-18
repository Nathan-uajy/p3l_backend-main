<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->bigIncrements('transaksiID');

            $table->unsignedBigInteger('pembeliID');
            $table->unsignedBigInteger('penitipID');
            $table->unsignedBigInteger('alamatID');

            $table->enum('tipe_transaksi', ['kirim', 'ambil'])->default('kirim');
            $table->enum('status', ['diproses', 'siap dikirim', 'siap diambil', 'selesai', 'hangus'])->default('diproses');
            $table->timestamp('waktu_transaksi')->useCurrent();

            $table->timestamps();

            // Foreign keys
            $table->foreign('pembeliID')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('penitipID')->references('penitipID')->on('penitips')->onDelete('cascade');
            $table->foreign('alamatID')->references('id')->on('alamats_ini')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
