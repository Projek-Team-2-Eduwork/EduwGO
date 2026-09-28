<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (! function_exists('setting')) {
    function setting($key, $default = null)
    {
        try {
            if (Schema::hasTable('settings')) {
                $setting = DB::table('settings')->where('key', $key)->first();

                return $setting ? $setting->value : $default;
            }
        } catch (Exception $e) {
            // Fallback aman jika database error / belum migrasi
        }

        return $default;
    }
}
