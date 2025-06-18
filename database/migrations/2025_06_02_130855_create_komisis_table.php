<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKomisisTable extends Migration
{
    public function up()
    {
        Schema::create('komisis', function (Blueprint $table) {
            $table->id('komisiID');
            $table->unsignedBigInteger('transaksiID');
            $table->unsignedBigInteger('penitipID');

            $table->decimal('komisi_hunter', 15, 2)->default(0); // Komisi untuk hunter (5%)
            $table->decimal('komsi_perusahaan', 15, 2)->default(0); // Komisi untuk ReuseMart

            $table->integer('jumlahKomisi')->default(0); // Total komisi
            $table->decimal('persentase', 10, 0)->default(0); // Persentase komisi yang diambil
            $table->date('tanggalKomisi'); // Tanggal pemberian komisi

            $table->timestamps();

            // Relasi
            $table->foreign('transaksiID')->references('transaksiID')->on('transaksis')->onDelete('cascade');
            $table->foreign('penitipID')->references('penitipID')->on('penitips')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('komisis');
    }
}
