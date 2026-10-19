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

    /**
     * Judul panjang untuk panel status di halaman detail pesanan
     * (lebih deskriptif daripada label badge yang dipakai di daftar).
     */
    public function headline(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::Paid => 'Pembayaran Lunas',
            self::Rented => 'Sedang Disewa',
            self::Returned => 'Sewa Selesai',
            self::Cancelled => 'Pesanan Dibatalkan',
            self::Expired => 'Pesanan Kedaluwarsa',
        };
    }

    /**
     * Penjelasan singkat arti status + langkah berikutnya bagi penyewa.
     */
    public function description(): string
    {
        return match ($this) {
            self::Pending => 'Pesanan sudah dibuat dan unit belum terkunci untuk Anda sampai pembayaran diterima.',
            self::Paid => 'Pembayaran sudah kami terima. Unit disiapkan sesuai jadwal sewa di bawah.',
            self::Rented => 'Unit sedang digunakan. Kembalikan sebelum waktu selesai sewa agar tidak terlambat.',
            self::Returned => 'Unit sudah dikembalikan dan pesanan ini selesai. Terima kasih sudah menyewa di EduwGo.',
            self::Cancelled => 'Pesanan ini dibatalkan, jadi unit kembali tersedia untuk penyewa lain.',
            self::Expired => 'Batas waktu pembayaran terlewat sehingga pesanan ini dibatalkan otomatis oleh sistem.',
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
