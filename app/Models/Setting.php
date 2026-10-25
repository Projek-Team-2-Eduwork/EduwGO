<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /**
     * Buang cache + memo request helper setting() supaya nilai baru langsung terbaca.
     */
    public static function flushCache(): void
    {
        Cache::forget('settings.all');
        app()->forgetInstance('settings.all');
    }
}
