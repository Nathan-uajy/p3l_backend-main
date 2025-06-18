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
        Schema::table('penitips', function (Blueprint $table) {
            $table->string('nik')->after('nama')->unique();
            $table->string('foto_ktp')->after('alamat');
        });
    }

    public function down(): void
    {
        Schema::table('penitips', function (Blueprint $table) {
            $table->dropColumn(['nik', 'foto_ktp']);
        });
    }

};
