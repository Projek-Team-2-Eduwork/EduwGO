<?php

namespace App\Models;

use App\Enums\BookingStatus;
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

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class); // Pastikan Model Vehicle ada (EG-4 berasumsi fondasi public udah siap)
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
     * Mengecek apakah status booking sedang disewa dan sudah melewati batas waktu (overdue)
     */
    public function isOverdue(): bool
    {
        return $this->status === BookingStatus::Rented && now()->greaterThan($this->end_at);
    }
}
