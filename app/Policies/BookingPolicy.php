<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;

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
        return $user->isStaff();
    }

    public function adminView(User $user, Booking $booking): bool
    {
        return $this->manages($user, $booking);
    }

    public function adminUpdate(User $user, Booking $booking): bool
    {
        return $this->manages($user, $booking);
    }

    private function manages(User $user, Booking $booking): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        $branchId = Vehicle::withTrashed()->whereKey($booking->vehicle_id)->value('branch_id');

        return $user->canManageBranch($branchId);
    }
}
