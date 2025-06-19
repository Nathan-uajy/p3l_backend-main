<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pembelis', function (Blueprint $table) {
            if (!Schema::hasColumn('pembelis', 'fcm_token')) {
                $table->string('fcm_token')->nullable()->after('password');
            }
        });

        Schema::table('penitips', function (Blueprint $table) {
            if (!Schema::hasColumn('penitips', 'fcm_token')) {
                $table->string('fcm_token')->nullable()->after('password');
            }
        });

        Schema::table('pegawais', function (Blueprint $table) {
            if (!Schema::hasColumn('pegawais', 'fcm_token')) {
                $table->string('fcm_token')->nullable()->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pembelis', function (Blueprint $table) {
            if (Schema::hasColumn('pembelis', 'fcm_token')) {
                $table->dropColumn('fcm_token');
            }
        });

        Schema::table('penitips', function (Blueprint $table) {
            if (Schema::hasColumn('penitips', 'fcm_token')) {
                $table->dropColumn('fcm_token');
            }
        });

        Schema::table('pegawais', function (Blueprint $table) {
            if (Schema::hasColumn('pegawais', 'fcm_token')) {
                $table->dropColumn('fcm_token');
            }
        });
    }
};
