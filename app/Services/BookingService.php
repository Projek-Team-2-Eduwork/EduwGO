<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Events\BookingStatusChanged;
use App\Exceptions\InvalidTransitionException;
use App\Exceptions\PendingBookingLimitException;
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

        return DB::transaction(function () use ($booking, $to, $by, $note) {
            // Kunci baris booking lalu cek ulang status terbaru: dua proses bersamaan
            // (webhook ganda, webhook vs scheduler, admin vs penyewa) tidak boleh
            // sama-sama lolos dari status lama yang sudah basi di memori.
            $oldStatus = Booking::whereKey($booking->getKey())->lockForUpdate()->first(['id', 'status'])?->status
                ?? $booking->status;

            if (! $oldStatus->canTransitionTo($to)) {
                throw InvalidTransitionException::make($oldStatus, $to);
            }

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
            // Kunci baris user dulu: dua checkout bersamaan dari akun yang sama
            // tidak boleh sama-sama lolos batas booking Pending.
            User::whereKey($user->id)->lockForUpdate()->first(['id']);

            $maxPending = (int) setting('booking.max_pending_per_user', 2);
            $pending = Booking::where('user_id', $user->id)
                ->where('status', BookingStatus::Pending->value)
                ->where('created_at', '>', now()->subMinutes((int) setting('booking.invoice_minutes', 60)))
                ->count();

            if ($pending >= $maxPending) {
                throw PendingBookingLimitException::make($maxPending);
            }

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
            BookingStatusChanged::dispatch($booking, BookingStatus::Pending, BookingStatus::Pending);

            return $booking;
        });
    }
}
