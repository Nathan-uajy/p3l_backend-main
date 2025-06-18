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
        Schema::create('penitips', function (Blueprint $table) {
            $table->id('penitipID');
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('nomorHP');
            $table->float('saldo')->default(0);
            $table->integer('poinLoyalitas')->default(0);
            $table->string('alamat');
            $table->string('role')->default('penitip');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penitips');
    }
};
