<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Exceptions\InvalidTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Models\Booking;
use App\Models\VehicleType;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\XenditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PesananController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private XenditService $xenditService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Booking::class);

        $query = Booking::with(['user', 'vehicle.type', 'payments', 'histories.changer']);

        // Filter Status
        if ($request->filled('status') && $request->status !== 'semua') {
            if ($statusEnum = BookingStatus::tryFrom($request->status)) {
                $query->where('status', $statusEnum->value);
            }
        }

        // Filter Tipe Kendaraan
        if ($request->filled('tipe')) {
            $query->whereHas('vehicle', function ($q) use ($request) {
                $q->where('vehicle_type_id', $request->tipe);
            });
        }

        // Filter Rentang Waktu (Beririsan)
        if ($request->filled('mulai') && $request->filled('selesai')) {
            $query->where('start_at', '<=', $request->selesai)
                ->where('end_at', '>=', $request->mulai);
        }

        // Search Query (q)
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->whereHas('vehicle', function ($vq) use ($q) {
                    $vq->where('name', 'like', "%{$q}%");
                })
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%");
            });
        }

        $bookings = $query->orderBy('start_at', 'desc')->paginate(12)->withQueryString();
        $types = VehicleType::orderBy('name')->pluck('name', 'id');

        return view('admin.pesanan.index', compact('bookings', 'types'));
    }

    public function show(string $code): View
    {
        $booking = Booking::where('code', $code)
            ->with(['user', 'vehicle.type', 'payments', 'histories.changer', 'latestPayment'])
            ->firstOrFail();

        Gate::authorize('adminView', $booking);

        return view('admin.pesanan.show', [
            'booking' => $booking,
            'payment' => $booking->latestPayment,
        ]);
    }

    public function updateStatus(UpdateBookingStatusRequest $request, string $code): RedirectResponse
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        Gate::authorize('adminUpdate', $booking);

        $v = $request->validated();

        try {
            app(BookingService::class)->transition(
                $booking,
                BookingStatus::from($v['to']),
                auth()->user(),
                $v['note'] ?? $v['reason'] ?? null
            );

            if ($v['to'] === 'cancelled') {
                $booking->update(['cancel_reason' => $v['reason']]);
            }

            return back()->with('success', 'Status pesanan berhasil diperbarui.');
        } catch (InvalidTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateNotes(Request $request, string $code): RedirectResponse
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        Gate::authorize('adminUpdate', $booking);

        $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        $booking->update([
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'Catatan admin berhasil diperbarui.');
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
