<?php

namespace Tests;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Pastikan cache settings tidak bocor antar test
        Cache::flush();
    }

    protected function defaultBranch(): Branch
    {
        return Branch::query()->firstOrCreate(['slug' => 'cabang-utama'], ['name' => 'Cabang Utama']);
    }

    protected function actingAsAdmin(?Branch $branch = null): User
    {
        Role::firstOrCreate(['name' => 'admin']);

        $branch ??= $this->defaultBranch();

        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        return $admin;
    }

    protected function actingAsSuperadmin(): User
    {
        Role::firstOrCreate(['name' => 'superadmin']);

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $this->actingAs($superadmin);

        return $superadmin;
    }
}
