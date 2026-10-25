<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'gateway_payload' => 'array',
        'instructions' => 'array',
        'needs_refund' => 'boolean',
        'refunded_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Uang masuk yang wajib di-refund manual dan belum ditandai selesai.
     */
    public function scopeAwaitingRefund(Builder $query): Builder
    {
        return $query->where('needs_refund', true)->whereNull('refunded_at');
    }

    public function scopeManagedBy(Builder $query, User $user): Builder
    {
        return $query->whereIn('booking_id', Booking::query()->managedBy($user)->select('id'));
    }
}
