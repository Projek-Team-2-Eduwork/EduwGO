<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * SATU-SATUNYA SUMBER KEBENARAN nama role.
     * File lain (model, route, controller, view) nunjuk ke sini.
     */
    public const SUPERADMIN = 'superadmin';
    public const ADMIN = 'admin';
    public const USER = 'user';

    /** Semua role yang valid. */
    public const ALL = [
        self::SUPERADMIN,
        self::ADMIN,
        self::USER,
    ];

    /** Role yang boleh masuk area admin. */
    public const STAFF = [
        self::SUPERADMIN,
        self::ADMIN,
    ];

    /** Siap pakai untuk middleware Spatie: -> "superadmin|admin" */
    public const STAFF_MIDDLEWARE = self::SUPERADMIN.'|'.self::ADMIN;

    /** Siap pakai untuk validasi: -> "in:superadmin,admin,user" */
    public const VALIDATION_RULE = 'in:'.self::SUPERADMIN.','.self::ADMIN.','.self::USER;

    public function run(): void
    {
        foreach (self::ALL as $name) {
            Role::firstOrCreate(['name' => $name]);
        }
    }
}