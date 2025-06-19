<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produks', function (Blueprint $table) {
            // Pastikan nama tabel users dan primary key-nya sesuai. Diasumsikan 'users' dan 'id'.
            $table->foreignId('cart_holder_user_id')
                  ->nullable()
                  ->after('status') // Atur posisi kolom jika diinginkan
                  ->constrained('pembelis','pembeliID') // Nama tabel users
                  ->onDelete('set null'); // Jika user dihapus, produk tidak lagi dipegang
        });
    }

    public function down(): void
    {
        Schema::table('produks', function (Blueprint $table) {
            // Perhatikan urutan penghapusan constraint sebelum drop kolom
            $table->dropForeign(['cart_holder_user_id']);
            $table->dropColumn('cart_holder_user_id');
        });
    }
};