<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('locations', 'branches');

        Schema::table('branches', function (Blueprint $table) {
            $table->json('social_links')->nullable()->after('operating_hours');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->renameColumn('location_id', 'branch_id');
        });

        if (DB::table('vehicles')->whereNull('branch_id')->exists()) {
            $branchId = DB::table('branches')->orderBy('id')->value('id') ?? DB::table('branches')->insertGetId([
                'name' => 'Cabang Utama',
                'slug' => 'cabang-utama',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('vehicles')->whereNull('branch_id')->update(['branch_id' => $branchId]);
        }

        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable(false)->change();
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->unsignedBigInteger('branch_id')->nullable()->change();
            $table->renameColumn('branch_id', 'location_id');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreign('location_id')->references('id')->on('branches')->restrictOnDelete();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('social_links');
        });

        Schema::rename('branches', 'locations');
    }
};
