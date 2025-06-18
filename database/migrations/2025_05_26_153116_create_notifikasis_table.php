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
        Schema::create('notifikasis', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('pembeliID')->nullable();
        $table->unsignedBigInteger('penitipID')->nullable();
        $table->string('peran'); // 'pembeli' atau 'penitip'
        $table->text('pesan');
        $table->boolean('dibaca')->default(false);
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasis');
    }
};
