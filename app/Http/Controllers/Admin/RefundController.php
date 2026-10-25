<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarkRefundedRequest;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RefundController extends Controller
{
    /**
     * Pembayaran yang masuk setelah pesanan tidak lagi Pending: wajib refund manual oleh admin.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Booking::class);

        $base = Payment::query()->managedBy($request->user())->where('needs_refund', true)->with('booking');

        return view('admin.refund.index', [
            'awaiting' => (clone $base)->whereNull('refunded_at')->latest('paid_at')->get(),
            'done' => (clone $base)->whereNotNull('refunded_at')->latest('refunded_at')->limit(20)->get(),
        ]);
    }

    public function update(MarkRefundedRequest $request, Payment $payment): RedirectResponse
    {
        if (! $payment->needs_refund || $payment->refunded_at !== null) {
            return back()->with('error', 'Pembayaran ini tidak sedang menunggu refund.');
        }

        $payment->update(['refunded_at' => now()]);

        return back()->with('success', 'Refund '.$payment->booking->code.' ditandai sudah dikirim.');
    }
}
