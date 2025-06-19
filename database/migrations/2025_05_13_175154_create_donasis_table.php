<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alamats', function (Blueprint $table) {
            $table->bigIncrements('alamatID');
            $table->unsignedBigInteger('user_id'); // foreign key ke user
            $table->string('namaAlamat');
            $table->string('namaPenerima')->nullable();
            $table->string('noHpPenerima')->nullable();
            $table->string('alamat');
            $table->string('kodePos')->nullable();
            $table->timestamps();

            // Jika kamu punya tabel users
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->unsignedBigInteger('pembeli_id')->nullable();
            // $table->unsignedBigInteger('penitip_id')->nullable();

            // $table->foreign('pembeli_id')->references('id')->on('pembelis')->onDelete('set null');
            // $table->foreign('penitip_id')->references('id')->on('penitips')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donasis');
    }
};