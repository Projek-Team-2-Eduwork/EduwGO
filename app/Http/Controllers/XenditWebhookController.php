<?php

namespace App\Http\Controllers;

use App\Services\XenditService;
use Illuminate\Http\Request;

class XenditWebhookController extends Controller
{
    /**
     * Menangani webhook dari Xendit.
     */
    public function handle(Request $request, XenditService $xenditService)
    {
        $token = config('services.xendit.webhook_token');
        $headerToken = $request->header('x-callback-token');

        // Verifikasi timing-safe dengan hash_equals
        if (empty($token) || empty($headerToken) || ! hash_equals($token, $headerToken)) {
            abort(401, 'Token webhook tidak valid atau tidak ditemukan.');
        }

        // Delegasikan pemrosesan ke service
        $xenditService->handleWebhook($request->all());

        return response()->json(['status' => 'ok']);
    }
}
