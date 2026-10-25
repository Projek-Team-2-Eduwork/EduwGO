<?php

namespace App\Http\Controllers;

use App\Exceptions\VehicleUnavailableException;
use App\Http\Requests\CheckoutRequest;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function show(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'start' => ['required', 'date', 'after_or_equal:now'],
            'days' => ['required', 'integer', 'min:1', 'max:'.setting('booking.max_days', 5)],
        ]);

        $start = Carbon::parse($request->start, config('app.timezone'))->setTimezone(config('app.timezone'));
        $days = (int) $request->days;
        $end = $start->copy()->addDays($days);

        $vehicle->loadMissing('branch');

        return view('checkout.show', compact('vehicle', 'start', 'end', 'days'));
    }

    public function store(CheckoutRequest $request, Vehicle $vehicle, BookingService $bookingService)
    {
        try {
            $booking = $bookingService->checkout(
                $request->user(),
                $vehicle,
                Carbon::parse($request->start, config('app.timezone'))->setTimezone(config('app.timezone')),
                (int) $request->days,
                $request->validated()
            );

            return redirect()->route('booking.waiting', $booking);
        } catch (VehicleUnavailableException $e) {
            return back()->withErrors(['start' => $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            // Detail error (respons Xendit, SQL) hanya ke log, bukan ke pengguna.
            Log::error('Checkout gagal.', [
                'vehicle_id' => $vehicle->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal memproses checkout. Silakan coba lagi atau hubungi admin.')->withInput();
        }
    }
}
