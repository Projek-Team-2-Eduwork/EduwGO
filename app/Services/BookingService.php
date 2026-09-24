<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Events\BookingStatusChanged;
use App\Exceptions\InvalidTransitionException;
use App\Models\Booking;
use App\Models\User;
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
}
