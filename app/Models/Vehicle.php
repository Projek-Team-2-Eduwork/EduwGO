<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'price_per_day' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Tipe motor (Matic, Cub, Sport, ...).
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id');
    }

    /**
     * Cabang pemilik unit ini.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeManagedBy(Builder $query, User $user): Builder
    {
        if ($user->isSuperadmin()) {
            return $query;
        }

        return $user->isBranchAdmin() && $user->branch_id !== null
            ? $query->where($query->getModel()->qualifyColumn('branch_id'), $user->branch_id)
            : $query->whereRaw('1 = 0');
    }

    /**
     * Booking untuk unit ini.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
