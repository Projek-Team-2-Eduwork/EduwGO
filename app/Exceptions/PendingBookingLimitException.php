<?php

namespace App\Exceptions;

/**
 * Penyewa sudah punya terlalu banyak booking Pending. Turunan
 * VehicleUnavailableException supaya CheckoutController menampilkan pesannya
 * sebagai error validasi form (bukan pesan gagal generik).
 */
class PendingBookingLimitException extends VehicleUnavailableException
{
    public static function make(int $max): self
    {
        return new self("Kamu masih punya {$max} pesanan yang menunggu pembayaran. Selesaikan atau batalkan pesanan itu dulu sebelum membuat pesanan baru.");
    }
}
