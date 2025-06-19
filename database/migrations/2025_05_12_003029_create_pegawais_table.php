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
        Schema::create('pegawais', function (Blueprint $table) {
            $table->id('pegawaiID');
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role'); // contoh: admin, owner, pegawai_gudang
            $table->string('jabatan'); 
            $table->date('tanggalLahir');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};
