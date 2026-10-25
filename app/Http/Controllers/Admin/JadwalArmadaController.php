<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Vehicle;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class JadwalArmadaController extends Controller
{
    public const DAYS = 14;

    /**
     * Grid unit × 14 hari dari booking aktif (pending, paid, rented) + buffer antar sewa.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Vehicle::class);

        $request->validate([
            'mulai' => ['nullable', 'date'],
            'branch' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $branchId = $user->effectiveBranchId($request->integer('branch') ?: null);
        $from = Carbon::parse($request->input('mulai', today()->toDateString()))->startOfDay();
        $to = $from->copy()->addDays(self::DAYS);
        $buffer = (int) setting('booking.buffer_minutes', 60);

        $vehicles = Vehicle::query()
            ->managedBy($user)
            ->where('is_active', true)
            ->when($user->isSuperadmin() && $branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->with(['type', 'bookings' => fn ($q) => $q->active()->overlapping($from, $to, $buffer)->orderBy('start_at')])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.jadwal-armada.index', [
            'vehicles' => $vehicles,
            'days' => collect(CarbonPeriod::create($from, $to->copy()->subDay())),
            'from' => $from,
            'buffer' => $buffer,
            'branches' => $user->isSuperadmin() ? Branch::orderBy('name')->pluck('name', 'id') : collect(),
        ]);
    }

    /**
     * Isi satu sel hari: booking yang menempati hari itu, atau hanya buffer-nya (unit tetap terkunci).
     *
     * @return array{booking: Booking, buffer: bool}|null
     */
    public static function cell(Vehicle $vehicle, CarbonInterface $day, int $buffer): ?array
    {
        $dayEnd = $day->copy()->addDay();

        foreach ([0, $buffer] as $pad) {
            foreach ($vehicle->bookings as $booking) {
                // Sewa terlambat tetap memblokir sampai ditandai kembali
                $end = $booking->isOverdue() ? now() : $booking->end_at;

                if ($booking->start_at->copy()->subMinutes($pad) < $dayEnd && $end->copy()->addMinutes($pad) > $day) {
                    return ['booking' => $booking, 'buffer' => $pad > 0];
                }
            }
        }

        return null;
    }

    public static function tone(Booking $booking): string
    {
        return $booking->isOverdue() ? 'danger' : match ($booking->status) {
            BookingStatus::Pending => 'info',
            BookingStatus::Paid => 'success',
            default => 'warning',
        };
    }
}
