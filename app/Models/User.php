<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_active', 'branch_id', 'phone', 'social_account', 'theme_preference'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'social_account' => 'array',
            'theme_preference' => 'string',
        ];
    }

    /**
     * Preferensi tema yang valid (light|dark), selain itu dianggap system.
     */
    public function preferredTheme(): string
    {
        return in_array($this->theme_preference, ['light', 'dark'], true)
            ? $this->theme_preference
            : 'system';
    }

    /**
     * Pesanan milik pengguna ini.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Cabang tempat admin bertugas (null untuk penyewa & superadmin).
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Superadmin: mengelola semua cabang, admin, dan pengaturan global.
     */
    public function isSuperadmin(): bool
    {
        return $this->hasRole('superadmin');
    }

    /**
     * Admin cabang (role `admin`): hanya data cabangnya sendiri.
     */
    public function isBranchAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Boleh masuk area /admin (superadmin atau admin cabang).
     */
    public function isStaff(): bool
    {
        return $this->hasAnyRole(['superadmin', 'admin']);
    }

    /**
     * Cabang yang dipakai untuk membatasi data admin. Superadmin bebas memilih ($requested,
     * null = semua cabang); admin cabang selalu cabangnya sendiri (0 bila belum punya cabang,
     * sehingga tidak ada data yang cocok).
     */
    public function effectiveBranchId(?int $requested = null): ?int
    {
        return $this->isSuperadmin() ? $requested : ($this->branch_id ?? 0);
    }

    /**
     * Boleh mengelola data milik cabang tertentu? Superadmin: semua cabang.
     * Admin cabang: hanya cabangnya; admin tanpa cabang tidak punya akses data.
     */
    public function canManageBranch(?int $branchId): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        return $this->isBranchAdmin() && $this->branch_id !== null && $this->branch_id === $branchId;
    }
}
