<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    /**
     * Menampilkan daftar pesanan milik pengguna yang sedang login.
     */
    public function index(Request $request)
    {
        $query = Booking::where('user_id', $request->user()->id)
            ->with('vehicle.type')
            ->latest();

        if ($request->filled('status') && BookingStatus::tryFrom($request->status)) {
            $query->where('status', $request->status);
        }

        $bookings = $query->paginate(10)->withQueryString();

        return view('booking.index', compact('bookings'));
    }

    /**
     * Menampilkan detail satu pesanan.
     */
    public function show(Request $request, $code)
    {
        $booking = Booking::where('code', $code)
            ->with(['vehicle.type', 'histories.changer', 'user'])
            ->firstOrFail();

        Gate::authorize('view', $booking);

        return view('booking.show', compact('booking'));
    }

    /**
     * Membatalkan pesanan yang berstatus Pending.
     */
    public function cancel(Request $request, $code)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        Gate::authorize('cancel', $booking);

        app(BookingService::class)->transition(
            $booking,
            BookingStatus::Cancelled,
            $request->user(),
            'Dibatalkan penyewa'
        );

        return redirect()->route('booking.index')
            ->with('success', "Pesanan {$booking->code} berhasil dibatalkan.");
    }
}