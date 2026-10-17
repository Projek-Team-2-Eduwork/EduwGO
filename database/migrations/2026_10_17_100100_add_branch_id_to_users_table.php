<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabang tempat seorang admin bertugas. Null untuk penyewa dan superadmin.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('is_active')
                ->constrained('branches')
                ->restrictOnDelete();
        });

        // Admin lama dulu mengelola semua unit; sekarang mereka ditempatkan di cabang pertama
        // supaya tetap bisa bekerja. Naikkan ke superadmin lewat Kelola Pengguna bila perlu.
        $branchId = DB::table('branches')->orderBy('id')->value('id');

        if ($branchId !== null) {
            $adminIds = DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('roles.name', 'admin')
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->pluck('model_has_roles.model_id');

            DB::table('users')->whereIn('id', $adminIds)->update(['branch_id' => $branchId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
