<?php

namespace App\Services;

class XenditService
{
    protected string $secretKey;

    public function __construct()
    {
        // Mengambil secret key dari config
        $this->secretKey = config('services.xendit.secret_key');
    }

    /**
     * Nanti dikerjakan di EG-13 (A-3)
     */
    public function createInvoice(array $params)
    {
        // TODO: Implementasi HTTP POST ke endpoint /v2/invoices Xendit
        // Butuh: external_id, amount, payer_email, description
    }

    /**
     * Nanti dikerjakan di EG-14 (A-4)
     */
    public function verifyWebhook(string $incomingToken): bool
    {
        // TODO: Cek apakah $incomingToken sama dengan config('services.xendit.webhook_token')
        return false;
    }
}