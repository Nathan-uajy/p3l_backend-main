<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('barangs', function (Blueprint $table) {
            $table->id('idProduk');
            $table->unsignedBigInteger('donasiID')->nullable();
            $table->unsignedBigInteger('penitipID');
            $table->string('namaProduk');
            $table->string('gambar')->nullable();
            $table->string('gambar2')->nullable();
            $table->text('deskripsi')->nullable();
            $table->decimal('harga', 10, 2)->default(0);
            $table->unsignedBigInteger('kategoriID')->nullable(); // Replace kategori with kategoriID
            $table->string('status')->default('aktif');
            $table->date('tglMulai')->nullable();
            $table->date('tglSelesai')->nullable();
            $table->date('garansi')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('penitipID')->references('penitipID')->on('penitips')->onDelete('cascade');
            $table->foreign('donasiID')->references('donasiID')->on('donasis')->onDelete('set null');
            $table->foreign('kategoriID')->references('idKategori')->on('kategori')->onDelete('set null'); 
            $table->foreign('kurirID')->references('pegawaiID')->on('pegawais')->onDelete('set null');
            // Foreign key to kategori table
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            $table->dropForeign(['penitipID']);
            $table->dropForeign(['kategoriID']);
        });
        Schema::dropIfExists('barangs');
    }
};