<?php

namespace Tests;

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

    protected function actingAsAdmin(): User
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        return $admin;
    }
}
