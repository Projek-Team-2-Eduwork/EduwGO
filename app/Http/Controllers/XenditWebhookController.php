<?php

namespace App\Http\Controllers;

use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    /**
     * Menangani webhook dari Xendit.
     */
    public function handle(Request $request, XenditService $xenditService)
    {
        $token = config('services.xendit.callback_token');
        $headerToken = $request->header('x-callback-token');

        // Verifikasi header x-callback-token (timing-safe)
        if (empty($token) || empty($headerToken) || ! hash_equals($token, $headerToken)) {
            Log::channel('xendit')->warning('Webhook Xendit ditolak: token tidak valid.', [
                'ip' => $request->ip(),
            ]);

            abort(403, 'Token webhook tidak valid.');
        }

        // Delegasikan pemrosesan ke service
        $xenditService->handleWebhook($request->all());

        return response()->json(['status' => 'ok']);
    }
}
