<?php

namespace App\Http\Controllers;

use App\Exceptions\VehicleUnavailableException;
use App\Http\Requests\CheckoutRequest;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CheckoutController extends Controller
{
    public function show(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'start' => ['required', 'date', 'after_or_equal:now'],
            'days' => ['required', 'integer', 'min:1', 'max:'.setting('booking.max_days', 5)],
        ]);

        $start = Carbon::parse($request->start);
        $days = (int) $request->days;
        $end = $start->copy()->addDays($days);

        return view('checkout.show', compact('vehicle', 'start', 'end', 'days'));
    }

    public function store(CheckoutRequest $request, Vehicle $vehicle, BookingService $bookingService)
    {
        try {
            $booking = $bookingService->checkout(
                $request->user(),
                $vehicle,
                Carbon::parse($request->start),
                (int) $request->days,
                $request->validated()
            );

            return redirect()->route('booking.waiting', $booking);
        } catch (VehicleUnavailableException $e) {
            return back()->withErrors(['start' => $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses checkout: '.$e->getMessage())->withInput();
        }
    }
}
