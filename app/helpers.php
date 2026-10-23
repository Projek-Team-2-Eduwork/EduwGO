<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (! function_exists('setting')) {
    function setting($key, $default = null)
    {
        try {
            // Cache seluruh settings (key => value); null = tabel belum ada, sengaja tidak di-cache
            $settings = Cache::rememberForever('settings.all', function () {
                if (! Schema::hasTable('settings')) {
                    return null;
                }

                return DB::table('settings')->pluck('value', 'key')->all();
            });

            if (is_array($settings)) {
                return $settings[$key] ?? $default;
            }
        } catch (Exception $e) {
            // Fallback aman jika database error / belum migrasi
        }

        return $default;
    }
}
