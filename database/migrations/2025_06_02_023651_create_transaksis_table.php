<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id('idTransaksi'); // Primary Key
            $table->unsignedBigInteger('pembeliID');
            $table->unsignedBigInteger('penitipID');
            $table->unsignedBigInteger('alamatID');
            $table->enum('tipe_transaksi', ['kirim', 'ambil'])->default('kirim');
            $table->enum('status', ['diproses', 'siap dikirim', 'siap diambil', 'selesai', 'hangus'])->default('diproses');
            $table->decimal('totalHarga', 15, 2);
            // $table->string('status');
            $table->date('tanggalTransaksi'); // Sesuai gambar menggunakan DATE, bukan TIMESTAMP
            $table->string('metodePembayaran')->nullable();
            $table->decimal('biayaPengiriman', 15, 2)->default(0);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->string('buktiPembayaran')->nullable();

            // Asumsi foreign key constraints (sesuaikan nama tabel & kolom PK jika perlu)
            // $table->foreign('penjadwalanID')->references('id')->on('penjadwalans')->onDelete('set null');
            // $table->foreign('pegawaiID')->references('pegawaiID')->on('pegawais')->onDelete('cascade'); // Asumsi PK di pegawais adalah pegawaiID
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};