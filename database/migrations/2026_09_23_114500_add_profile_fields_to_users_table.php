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
        Schema::table('users', function (Blueprint $table) {
            // Nomor WhatsApp, dipakai saat checkout & dicek admin.
            $table->string('phone', 20)->nullable()->after('email');

            // Instagram / Facebook username penyewa.
            $table->json('social_account')->nullable()->after('phone');

            // light | dark | system (default: system).
            $table->string('theme_preference', 10)->default('system')->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'social_account', 'theme_preference']);
        });
    }
};
