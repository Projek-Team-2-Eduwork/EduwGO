<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengaman endpoint cron eksternal (hosting tanpa scheduler, docs/DEPLOY.md).
 * Pemanggil wajib kirim header `Authorization: Bearer <CRON_TOKEN>`.
 * Bila CRON_TOKEN kosong, endpoint selalu ditolak.
 */
class VerifyCronToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) config('services.cron.token');
        $headerToken = (string) $request->bearerToken();

        // Perbandingan timing-safe, sama seperti verifikasi webhook Xendit
        if ($token === '' || $headerToken === '' || ! hash_equals($token, $headerToken)) {
            abort(401, 'Token cron tidak valid.');
        }

        return $next($request);
    }
}
