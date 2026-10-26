<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Branch extends Model
{
    use HasFactory;

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

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function admins(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

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

    public function mapsUrl(): ?string
    {
        return filled($this->address)
            ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($this->name.', '.$this->address)
            : null;
    }

    /**
     * Kontak untuk email: data cabang, tiap field kosong jatuh ke setting global,
     * dua-duanya kosong = field dibuang supaya baris disembunyikan.
     */
    public static function mailContact(?self $branch = null): array
    {
        $phone = $branch?->phone ?: setting('contact.whatsapp');

        return array_filter([
            'name' => $branch?->name ?: setting('brand.name', config('app.name')),
            'address' => $branch?->address ?: setting('contact.address'),
            'hours' => $branch?->operating_hours ?: setting('contact.hours'),
            'phone' => $phone,
            'whatsapp' => (new self(['phone' => $phone]))->whatsappUrl(),
        ]);
    }
}
