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
        Schema::create('request_donasi', function (Blueprint $table) {
            $table->id('idReqDonasi');
            $table->string('namaReqDonasi');
            $table->string('kategoriReqDonasi');
            $table->unsignedBigInteger('organisasiID');
            $table->unsignedBigInteger('donasiID')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_donasis');
    }
};
