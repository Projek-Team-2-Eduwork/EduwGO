<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine whether the user can view the booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id;
    }

    /**
     * Determine whether the user can cancel the booking.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id && $booking->status === BookingStatus::Pending;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function adminView(User $user, Booking $booking): bool
    {
        return $user->hasRole('admin');
    }

    public function adminUpdate(User $user, Booking $booking): bool
    {
        return $user->hasRole('admin');
    }
}
