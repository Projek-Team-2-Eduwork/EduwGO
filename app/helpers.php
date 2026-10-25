<?php

use App\Models\Payment;
use App\Services\XenditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (! function_exists('setting')) {
    function setting($key, $default = null)
    {
        // Memo per request di container: satu kali ambil cache (bukan satu query per panggilan).
        // Diinvalidasi oleh App\Models\Setting::flushCache() saat ada perubahan.
        if (! app()->bound('settings.all')) {
            try {
                // null = tabel belum ada, sengaja tidak di-cache
                $settings = Cache::rememberForever('settings.all', function () {
                    if (! Schema::hasTable('settings')) {
                        return null;
                    }

                    return DB::table('settings')->pluck('value', 'key')->all();
                });
            } catch (Exception $e) {
                // Fallback aman jika database error / belum migrasi
                $settings = null;
            }

            app()->instance('settings.all', is_array($settings) ? $settings : []);
        }

        return app('settings.all')[$key] ?? $default;
    }
}

if (! function_exists('rupiah')) {
    function rupiah(int|float|string|null $amount): string
    {
        return 'Rp'.number_format((float) $amount, 0, ',', '.');
    }
}

if (! function_exists('payment_label')) {
    function payment_label(?Payment $payment): string
    {
        if ($payment?->status !== 'paid') {
            return 'Belum dibayar';
        }

        $method = $payment->method === 'cash' ? 'Tunai' : ($payment->payment_channel ? XenditService::paymentLabel($payment->payment_channel, $payment->method) : 'Pembayaran online');

        return 'Lunas · '.str_replace('Xendit', 'Pembayaran online', $method);
    }
}

if (! function_exists('avif_url')) {
    /**
     * URL versi .avif di samping gambar statis public/ (mis. images/vehicles/x.jpg → x.avif), null bila tidak ada.
     * Upload admin (storage/...) tidak punya versi AVIF (GD server tanpa imageavif), jadi tetap format asli.
     */
    function avif_url(?string $path): ?string
    {
        static $found = [];

        if (blank($path) || ! str_starts_with($path, 'images/')) {
            return null;
        }

        $avif = preg_replace('/\.(jpe?g|png|webp)$/i', '.avif', $path);

        return ($found[$avif] ??= is_file(public_path($avif))) ? asset($avif) : null;
    }
}
