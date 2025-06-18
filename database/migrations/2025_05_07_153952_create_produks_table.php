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
        Schema::create('produks', function (Blueprint $table) {
            $table->bigIncrements('idProduk'); // sesuai dengan primaryKey di model
            $table->unsignedBigInteger('donasiID')->nullable();
            $table->unsignedBigInteger('penitipID')->nullable();
            $table->string('namaProduk');
            $table->text('deskripsi')->nullable();
            $table->float('harga')->nullable(false);
            $table->string('kategori')->nullable();
            $table->string('status')->nullable();
            $table->date('tglMulai')->nullable();
            $table->date('tglSelesai')->nullable();
            $table->date('garansi')->nullable();
            $table->string('gambar')->nullable();
            // timestamps false sesuai model, jadi tidak perlu $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produks');
    }
};