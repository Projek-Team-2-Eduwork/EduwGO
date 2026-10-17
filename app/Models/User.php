<?php

namespace App\Models;

use App\Notifications\VerifyEmailCode;
use App\Services\EmailVerificationCodeService;
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
     * Kirim kode verifikasi 6 angka (menggantikan link verifikasi bawaan Laravel).
     */
    public function sendEmailVerificationNotification(): void
    {
        $code = app(EmailVerificationCodeService::class)->issue($this);

        // Kirim email setelah respons dikirim ke browser: SMTP bisa makan beberapa detik
        // dan tidak perlu menahan halaman pendaftaran (tanpa butuh queue worker).
        app()->terminating(fn () => $this->notify(new VerifyEmailCode($code)));
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isSuperadmin(): bool
    {
        return $this->hasRole('superadmin');
    }

    public function isBranchAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(['superadmin', 'admin']);
    }

    public function effectiveBranchId(?int $requested = null): ?int
    {
        return $this->isSuperadmin() ? $requested : ($this->branch_id ?? 0);
    }

    public function canManageBranch(?int $branchId): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        return $this->isBranchAdmin() && $this->branch_id !== null && $this->branch_id === $branchId;
    }
}
