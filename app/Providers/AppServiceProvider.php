<?php

namespace App\Providers;

use App\Services\BranchContext;
use App\Services\PendingRegistrationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BranchContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Kirim ulang kode verifikasi email: 3/menit + 10/jam per akun. Tiap kode
        // memberi 5 kali tebakan, jadi batas per jam menahan brute force 6 angka.
        // Pendaftar yang belum punya akun dihitung per email pendaftaran tertunda.
        RateLimiter::for('verification-code', function (Request $request) {
            $key = (string) ($request->user()?->id
                ?? $request->session()->get(PendingRegistrationService::SESSION_KEY)
                ?? $request->ip());

            return [
                Limit::perMinute(3)->by('minute:'.$key),
                Limit::perHour(10)->by('hour:'.$key),
            ];
        });
    }
}
