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
            self::Pending => 'Menunggu pembayaran',
            self::Paid => 'Lunas',
            self::Rented => 'Sedang disewa',
            self::Returned => 'Sudah kembali',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Batas bayar terlewat',
        };
    }

    /**
     * Judul panjang untuk panel status di halaman detail pesanan
     * (lebih deskriptif daripada label badge yang dipakai di daftar).
     */
    public function headline(): string
    {
        return $this->label();
    }

    /**
     * Penjelasan singkat arti status + langkah berikutnya bagi penyewa.
     */
    public function description(): string
    {
        return match ($this) {
            self::Pending => 'Unit dicadangkan sampai batas bayar. Selesaikan pembayaran untuk mengonfirmasi pesanan Anda.',
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
