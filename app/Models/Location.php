<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Location extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Slug otomatis dari nama saat CREATE saja; update tidak meregenerasi
     * agar URL/filter yang sudah beredar tetap stabil.
     */
    protected static function booted(): void
    {
        static::creating(function (Location $location) {
            if (blank($location->slug)) {
                $base = Str::slug((string) $location->name);
                $slug = $base;
                $suffix = 2;

                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$suffix++;
                }

                $location->slug = $slug;
            }
        });
    }

    /**
     * Unit kendaraan yang berada di lokasi ini.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
