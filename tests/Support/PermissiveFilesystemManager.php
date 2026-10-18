<?php

namespace Tests\Support;

use Illuminate\Filesystem\FilesystemManager;

/**
 * FilesystemManager khusus test.
 *
 * Disk uji dari Storage::fake("public") dibuat Flysystem sebagai direktori
 * private (0700) milik user yang menjalankan test. Di container, test bisa
 * dijalankan sebagai root (docker exec) maupun sebagai user sail, sehingga
 * sisa folder milik user lain bikin Storage::fake()->cleanDirectory() gagal
 * dengan "Failed to open directory: Permission denied".
 *
 * Karena itu semua disk lokal di test dipaksa permisif supaya aman lintas user.
 */
class PermissiveFilesystemManager extends FilesystemManager
{
    public function createLocalDriver(array $config, string $name = 'local')
    {
        $config['permissions'] = [
            'file' => ['public' => 0o666, 'private' => 0o666],
            'dir' => ['public' => 0o777, 'private' => 0o777],
        ];

        return parent::createLocalDriver($config, $name);
    }
}
