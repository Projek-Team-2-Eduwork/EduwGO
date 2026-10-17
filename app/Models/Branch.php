<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Branch extends Model
{
    use HasFactory;

    /** Kunci media sosial yang didukung => label tampilan. */
    public const SOCIALS = [
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'social_links' => 'array',
    ];

    /**
     * Slug otomatis dari nama saat CREATE saja; update tidak meregenerasi
     * agar URL/filter yang sudah beredar tetap stabil.
     */
    protected static function booted(): void
    {
        static::creating(function (Branch $branch) {
            if (blank($branch->slug)) {
                $base = Str::slug((string) $branch->name);
                $slug = $base;
                $suffix = 2;

                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$suffix++;
                }

                $branch->slug = $slug;
            }
        });
    }

    /**
     * Unit kendaraan milik cabang ini.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Admin yang bertugas di cabang ini.
     */
    public function admins(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Cabang yang tampil ke penyewa.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Link media sosial yang terisi: [label => url].
     *
     * @return array<string, string>
     */
    public function socialLinks(): array
    {
        $links = [];

        foreach (self::SOCIALS as $key => $label) {
            $url = trim((string) ($this->social_links[$key] ?? ''));

            if ($url !== '' && $url !== '#') {
                $links[$label] = $url;
            }
        }

        return $links;
    }

    /**
     * URL wa.me dari nomor kontak cabang (angka saja; 0 awal diganti 62), null bila kosong.
     */
    public function whatsappUrl(?string $text = null): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return 'https://wa.me/'.$digits.($text ? '?text='.rawurlencode($text) : '');
    }
}
