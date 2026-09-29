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

    public function success(Request $request, $code)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        $lunas = [BookingStatus::Paid, BookingStatus::Rented, BookingStatus::Returned];

        if (in_array($booking->status, $lunas, true)) {
            return view('booking.success', ['booking' => $booking, 'isPaid' => true]);
        }

        if ($booking->status === BookingStatus::Pending) {
            return view('booking.success', ['booking' => $booking, 'isPaid' => false]);
        }

        return redirect()->route('booking.failed', $code);
    }

    public function failed(Request $request, $code)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        $lunas = [BookingStatus::Paid, BookingStatus::Rented, BookingStatus::Returned];

        if (in_array($booking->status, $lunas, true)) {
            return redirect()->route('booking.success', $code);
        }

        return view('booking.failed', ['booking' => $booking]);
    }

    public function status(Request $request, $code)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        $lunas = [BookingStatus::Paid, BookingStatus::Rented, BookingStatus::Returned];

        return response()->json([
            'status' => $booking->status->value,
            'paid' => in_array($booking->status, $lunas, true),
        ]);
    }

    /**
     * Menyimpan data booking lama ke session untuk form booking ulang (rebook)
     * dan mengarahkan pengguna ke halaman detail kendaraan dengan modal terbuka otomatis.
     */
    public function ulang(Request $request, $code)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        // Pastikan hanya pemilik yang bisa booking ulang pesanannya sendiri
        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak diizinkan mengakses pesanan ini.');
        }

        $start = $booking->start_at;

        // Jika waktu sewa lama sudah berlalu, bulatkan ke 1 jam ke depan dari sekarang
        if ($start->isPast()) {
            $start = now()->startOfHour()->addHour();
        }

        // Simpan ke session untuk di-consume (pull) oleh modal di halaman detail
        session([
            'booking.start_at' => $start->format('Y-m-d\TH:i'),
            'booking.days' => $booking->duration_days,
        ]);

        return redirect()->route('kendaraan.detail', [
            'vehicle' => $booking->vehicle,
            'ulang' => 1,
        ]);
    }
}
