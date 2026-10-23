<?php

namespace Tests;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Spatie\Permission\Models\Role;
use Tests\Support\PermissiveFilesystemManager;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Disk uji dibuat dengan izin 0700 milik user yang menjalankan test,
        // sehingga sisa run user lain (root vs sail) bikin Permission denied.
        // umask 022 bikin mkdir(0777) jadi 0755, jadi umask ikut dinolkan.
        umask(0000);
        $this->app->instance('filesystem', new PermissiveFilesystemManager($this->app));
        Facade::clearResolvedInstance('filesystem');

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
