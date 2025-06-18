<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMerchandiseTable extends Migration
{
    public function up()
    {
        Schema::create('merchandises', function (Blueprint $table) {
            $table->id('merchandiseID');
            $table->string('nama');
            $table->integer('stok');         // stok merchandise
            $table->integer('hargaMerch');     // poin yang dibutuhkan untuk klaim
            $table->string('foto')->nullable(); // nama file gambar
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('merchandise');
    }
}
