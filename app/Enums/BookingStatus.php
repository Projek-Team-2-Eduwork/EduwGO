<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Rented = 'rented';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Lunas',
            self::Rented => 'Sedang disewa',
            self::Returned => 'Sudah kembali',
            self::Cancelled => 'Batal',
            self::Expired => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'info',
            self::Paid => 'success',
            self::Rented => 'warning',
            self::Returned => 'neutral',
            self::Cancelled => 'danger',
            self::Expired => 'danger',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Paid, self::Expired, self::Cancelled],
            self::Paid => [self::Rented, self::Cancelled],
            self::Rented => [self::Returned],
            default => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::Paid, self::Rented], true);
    }
}
