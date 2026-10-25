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
        $user = $request->user();

        $query = Booking::where('user_id', $user->id)
            ->with(['vehicle.type', 'vehicle.branch', 'payments', 'latestPayment'])
            ->latest();

        if ($request->filled('status') && BookingStatus::tryFrom($request->status)) {
            $query->where('status', $request->status);
        } else {
            $statuses = match ($request->query('tab', 'aktif')) {
                'menunggu' => [BookingStatus::Pending],
                'selesai' => [BookingStatus::Returned, BookingStatus::Cancelled, BookingStatus::Expired],
                default => [BookingStatus::Pending, BookingStatus::Paid, BookingStatus::Rented],
            };
            $query->whereIn('status', $statuses);
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
            ->with(['vehicle.type', 'vehicle.branch', 'histories.changer', 'user', 'latestPayment'])
            ->firstOrFail();

        Gate::authorize('view', $booking);

        // Panel status & ringkasan pembayaran butuh transaksi terakhir,
        // sedangkan timeline butuh riwayat urut dari paling lama.
        $payment = $booking->latestPayment;
        $riwayat = $booking->histories->sortBy('created_at')->values();

        return view('booking.show', compact('booking', 'payment', 'riwayat'));
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
