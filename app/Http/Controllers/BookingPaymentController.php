<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\XenditService;
use Illuminate\Http\Request;

class BookingPaymentController extends Controller
{
    public function waiting(Request $request, $code, XenditService $xenditService)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        // Authorization sederhana
        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        // Redirect jika status bukan pending
        if ($booking->status !== BookingStatus::Pending) {
            if (in_array($booking->status, [BookingStatus::Paid, BookingStatus::Rented, BookingStatus::Returned])) {
                return redirect()->route('booking.success', $booking->code);
            }

            return redirect()->route('booking.failed', $booking->code);
        }

        try {
            $payment = $xenditService->createInvoice($booking);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return view('booking.waiting', compact('booking', 'payment'));
    }

    public function success($code)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        return view('booking.success', compact('booking'));
    }

    public function failed($code)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        return view('booking.failed', compact('booking'));
    }
}
