<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Exceptions\InvalidTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Services\BookingService;
use App\Services\XenditService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PesananController extends Controller
{
    public function __construct(
        private XenditService $xenditService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Booking::class);

        // Kartu ringkasan status: jumlah global per kategori (EG-51)
        // Satu query GROUP BY untuk semua status (bukan satu count per status)
        $counts = $this->scopedQuery($request)->toBase()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $summary = collect(BookingStatus::cases())->mapWithKeys(fn ($s) => [$s->value => (int) ($counts[$s->value] ?? 0)])->all();

        $query = $this->scopedQuery($request)->with('vehicle.type');

        // Filter Status — aturan tunggal direuse kartu ringkasan (EG-51)
        $this->applyStatusFilter($query, $request->input('status'));

        // Filter Tipe Kendaraan
        if ($request->filled('tipe')) {
            $query->whereHas('vehicle', function ($q) use ($request) {
                $q->where('vehicle_type_id', $request->tipe);
            });
        }

        // Filter Rentang Waktu (Beririsan); boleh diisi salah satu
        $request->validate(['mulai' => ['nullable', 'date'], 'selesai' => ['nullable', 'date']]);
        $query->when($request->filled('selesai'), fn ($q) => $q->where('start_at', '<=', Carbon::parse($request->selesai)))
            ->when($request->filled('mulai'), fn ($q) => $q->where('end_at', '>=', Carbon::parse($request->mulai)));

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
        $branches = $request->user()->isSuperadmin() ? Branch::orderBy('name')->pluck('name', 'id') : collect();

        return view('admin.pesanan.index', compact('bookings', 'types', 'summary', 'branches'));
    }

    private function scopedQuery(Request $request): Builder
    {
        $user = $request->user();
        $branchId = $request->integer('branch') ?: null;

        return Booking::query()
            ->managedBy($user)
            ->when($user->isSuperadmin() && $branchId !== null, fn (Builder $q) => $q->whereIn(
                'vehicle_id',
                Vehicle::withTrashed()->where('branch_id', $branchId)->select('id'),
            ));
    }

    /**
     * Terapkan filter status pada query. Satu-satunya tempat aturan status
     * dipakai tabel dan ringkasan status.
     */
    private function applyStatusFilter(Builder $query, ?string $status): void
    {
        if (! filled($status)) {
            return;
        }

        if ($status === 'terlambat') {
            $query->overdue();

            return;
        }

        if ($status === 'aktif') {
            $query->active();

            return;
        }

        $statusEnum = BookingStatus::tryFrom($status);
        if ($statusEnum) {
            $query->where('status', $statusEnum->value);
        }
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
        Gate::authorize('adminUpdate', $booking);

        if ($booking->status !== BookingStatus::Pending) {
            return back()->with('error', 'Hanya pesanan Pending yang bisa dicek ke Xendit.');
        }

        // syncPaymentStatus menangani payment request v3 dan invoice v2 sekaligus
        try {
            $ok = $this->xenditService->syncPaymentStatus($booking);
        } catch (\Throwable $e) {
            Log::channel('xendit')->error('Admin Cek Status gagal.', [
                'booking' => $booking->code,
                'error' => $e->getMessage(),
            ]);
            $ok = false;
        }

        if (! $ok) {
            return back()->with('error', 'Gagal mengecek status ke Xendit. Coba lagi beberapa saat.');
        }

        return back()->with('success', 'Status Xendit berhasil disinkronkan: '.$booking->fresh()->status->label());
    }

    public function payCash(Request $request, string $code)
    {
        $booking = Booking::where('code', $code)->firstOrFail();
        Gate::authorize('adminUpdate', $booking);

        $request->validate([
            'amount' => 'required|integer|min:1',
            'note' => 'nullable|string|max:500',
        ]);

        if ($booking->status !== BookingStatus::Pending) {
            return back()->with('error', 'Hanya booking pending yang bisa dibayar tunai.');
        }

        // Tunai = pelunasan penuh: nominal wajib sama dengan total tagihan
        if ((int) $request->amount !== (int) round((float) $booking->total_amount)) {
            $pesan = 'Nominal tunai harus sama dengan total tagihan Rp '.number_format((float) $booking->total_amount, 0, ',', '.').'.';

            return back()->withErrors(['amount' => $pesan])->withInput()->with('error', $pesan);
        }

        try {
            DB::transaction(function () use ($request, $booking) {
                Payment::create([
                    'booking_id' => $booking->id,
                    'method' => 'cash',
                    'status' => 'paid',
                    'paid_at' => now(),
                    'amount' => (int) $request->amount,
                    'gateway_reference' => null,
                    'gateway_url' => null,
                ]);

                // Payment Xendit yang masih pending ditutup listener ExpirePendingPayments
                $note = trim('Bayar tunai'.($request->filled('note') ? ': '.$request->note : ''));
                app(BookingService::class)->transition($booking, BookingStatus::Paid, auth()->user(), $note);
            });
        } catch (InvalidTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.pesanan.show', $code)->with('success', 'Pembayaran tunai berhasil dicatat.');
    }
}
