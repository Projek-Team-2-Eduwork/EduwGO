<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\PaymentService;
use App\Services\XenditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PesananController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private XenditService $xenditService
    ) {}

    public function show(string $code): View
    {
        $booking = Booking::where('code', $code)->with('latestPayment')->firstOrFail();

        return view('admin.pesanan.show', [
            'booking' => $booking,
            'payment' => $booking->latestPayment,
        ]);
    }

    public function cekStatus(string $code, Request $request): RedirectResponse
    {
        $booking = Booking::where('code', $code)->firstOrFail();
        $payment = $booking->latestPayment;

        if (! $payment || $payment->status !== 'pending') {
            return back()->with('error', 'Tidak ada invoice pending untuk disinkronkan.');
        }

        try {
            $invoice = $this->xenditService->getInvoice($payment->gateway_reference);
            $this->paymentService->applyInvoiceStatus($payment, $invoice);

            return back()->with('success', 'Status Xendit berhasil disinkronkan: '.$invoice['status']);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
