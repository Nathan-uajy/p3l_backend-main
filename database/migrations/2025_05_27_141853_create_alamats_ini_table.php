<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alamats_ini', function (Blueprint $table) {
            $table->bigIncrements('alamatID');
            $table->unsignedBigInteger('pembeliID');

            $table->string('namaAlamat');
            $table->string('namaPenerima');
            $table->string('noHpPenerima');
            $table->string('alamat');
            $table->string('kodePos');
            $table->timestamps();

            // FOREIGN KEY (aktif kalau struktur pembelis cocok)
            $table->foreign('pembeliID')
                  ->references('pembeliID')
                  ->on('pembelis')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alamats_ini');
    }
};
