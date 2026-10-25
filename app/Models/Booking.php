<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Services\XenditService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'price_per_day' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => BookingStatus::class, // Tambahan cast Enum BookingStatus
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Kendaraan yang disewa.
     *
     * withTrashed(): unit yang dihapus admin (soft delete) tetap harus muncul di
     * riwayat pesanan, kalau tidak $booking->vehicle jadi null dan halaman pesanan error.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    public function histories()
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * Batas bayar online tetap: waktu booking dibuat + setting invoice_minutes. Tidak
     * ikut bergeser saat penyewa membuat payment baru (ganti metode bayar).
     */
    public function onlinePaymentDeadline(): CarbonInterface
    {
        return $this->created_at->copy()->addMinutes((int) setting('booking.invoice_minutes', 60));
    }

    /**
     * Batas bayar efektif. Booking COD dibayar saat pengambilan, jadi batasnya
     * start_at + buffer; selain itu batas bayar online.
     */
    public function paymentDeadline(): CarbonInterface
    {
        if (self::pendingCod($this->payments())->exists()) {
            return $this->start_at->copy()->addMinutes((int) setting('booking.buffer_minutes', 60));
        }

        return $this->onlinePaymentDeadline();
    }

    public function isPaymentDeadlinePassed(): bool
    {
        return ! now()->lessThan($this->paymentDeadline());
    }

    /**
     * Booking Pending yang batas bayarnya sudah lewat (kandidat auto-expire).
     */
    public function scopePaymentDeadlinePassed(Builder $query): Builder
    {
        $cod = fn (Builder $q) => self::pendingCod($q);

        return $query->where('status', BookingStatus::Pending->value)
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $online) => $online
                    ->whereDoesntHave('payments', $cod)
                    ->where('created_at', '<=', now()->subMinutes((int) setting('booking.invoice_minutes', 60))))
                ->orWhere(fn (Builder $bayarDiTempat) => $bayarDiTempat
                    ->whereHas('payments', $cod)
                    ->where('start_at', '<=', now()->subMinutes((int) setting('booking.buffer_minutes', 60)))));
    }

    /**
     * Filter payment tunai COD yang masih menunggu dibayar di cabang.
     */
    public static function pendingCod($query)
    {
        return $query->where('method', 'cash')
            ->where('status', 'pending')
            ->where('payment_channel', XenditService::COD);
    }

    public function scopeManagedBy(Builder $query, User $user): Builder
    {
        if ($user->isSuperadmin()) {
            return $query;
        }

        return $query->whereIn(
            $query->getModel()->qualifyColumn('vehicle_id'),
            Vehicle::withTrashed()->managedBy($user)->select('id'),
        );
    }

    /**
     * Booking yang masih memblokir unit (pending, paid, rented) — dipakai AvailabilityService.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            BookingStatus::Pending->value,
            BookingStatus::Paid->value,
            BookingStatus::Rented->value,
        ]);
    }

    /**
     * Booking yang rentangnya bentrok dengan [start, end], memperhitungkan buffer (menit).
     * Konflik: (start_at − buffer) < end AND (end_at + buffer) > start.
     *
     * Sewa Rented yang sudah lewat end_at (overdue) dianggap belum selesai: unit
     * belum kembali secara fisik, jadi tetap memblokir semua rentang sampai
     * admin menandai Returned.
     */
    public function scopeOverlapping(Builder $query, CarbonInterface $start, CarbonInterface $end, int $buffer): Builder
    {
        return $query
            ->where('start_at', '<', $end->copy()->addMinutes($buffer))
            ->where(fn (Builder $q) => $q
                ->where('end_at', '>', $start->copy()->subMinutes($buffer))
                ->orWhere(fn (Builder $overdue) => $overdue
                    ->where('status', BookingStatus::Rented->value)
                    ->where('end_at', '<', now())));
    }

    /**
     * Mengecek apakah status booking sedang disewa dan sudah melewati batas waktu (overdue)
     */
    public function isOverdue(): bool
    {
        return $this->status === BookingStatus::Rented && now()->greaterThan($this->end_at);
    }

    /**
     * Link chat WhatsApp ke nomor penyewa (08xx dinormalisasi ke 62xx).
     */
    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->customer_phone);

        if ($digits === '') {
            return null;
        }

        return 'https://wa.me/'.(str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits);
    }

    /**
     * Mendapatkan key untuk route model binding (digunakan di URL checkout redirect).
     */
    public function getRouteKey()
    {
        return $this->code;
    }

    /**
     * Scope query untuk pesanan yang terlambat (status rented, end_at lewat).
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', BookingStatus::Rented->value)
            ->where('end_at', '<', now());
    }
}
