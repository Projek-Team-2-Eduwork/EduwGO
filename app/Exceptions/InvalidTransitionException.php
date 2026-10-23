<?php

namespace App\Exceptions;

use App\Enums\BookingStatus;
use RuntimeException;

class InvalidTransitionException extends RuntimeException
{
    public static function make(BookingStatus $from, BookingStatus $to): self
    {
        return new self("Tidak dapat melakukan transisi status dari '{$from->value}' ke '{$to->value}'.");
    }
}
