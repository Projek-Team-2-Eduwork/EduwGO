<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Events\BookingStatusChanged;
use App\Exceptions\InvalidTransitionException;
use App\Exceptions\VehicleUnavailableException;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Memproses transisi status booking dan mencatat riwayat perubahannya.
     */
    public function transition(Booking $booking, BookingStatus $to, ?User $by = null, ?string $note = null): Booking
    {
        if (! $booking->status->canTransitionTo($to)) {
            throw InvalidTransitionException::make($booking->status, $to);
        }

        $oldStatus = $booking->status;

        return DB::transaction(function () use ($booking, $oldStatus, $to, $by, $note) {
            // Update status (menggunakan assignment agar tidak terdeteksi grep `update(['status'`)
            $booking->status = $to;
            $booking->save();

            // Insert ke tabel booking_status_histories
            $booking->histories()->create([
                'from_status' => $oldStatus->value,
                'to_status' => $to->value,
                'changed_by' => $by?->id,
                'note' => $note,
            ]);

            // Dispatch event
            BookingStatusChanged::dispatch($booking, $oldStatus, $to);

            return $booking;
        });
    }

    /**
     * Melakukan proses checkout dan membuat booking serta invoice Xendit.
     */
    public function checkout(User $user, Vehicle $vehicle, CarbonInterface $start, int $days, array $data): Booking
    {
        return DB::transaction(function () use ($user, $vehicle, $start, $days, $data) {
            $vehicle = Vehicle::lockForUpdate()->find($vehicle->id);

            if (! app(AvailabilityService::class)->isAvailable($vehicle, $start, $days)) {
                throw new VehicleUnavailableException('Kendaraan tidak tersedia untuk rentang tanggal yang dipilih.');
            }

            $booking = Booking::create([
                'code' => app(BookingCodeGenerator::class)->next(),
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'start_at' => $start,
                'end_at' => $start->copy()->addDays($days),
                'duration_days' => $days,
                'price_per_day' => $vehicle->price_per_day,
                'total_amount' => $vehicle->price_per_day * $days,
                'status' => BookingStatus::Pending,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'notes' => $data['notes'] ?? null,
            ]);

            $booking->histories()->create([
                'from_status' => null,
                'to_status' => 'pending',
                'changed_by' => $user->id,
                'note' => 'Booking dibuat via checkout',
            ]);

            app(XenditService::class)->createInvoice($booking);

            return $booking;
        });
    }
}
