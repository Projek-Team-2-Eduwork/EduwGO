<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Kelola pengguna & admin cabang: superadmin saja. */
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function setRole(User $currentUser, User $targetUser, string $role): bool
    {
        if (! $currentUser->isSuperadmin()) {
            return false;
        }

        // Tidak boleh mengubah role dirinya sendiri (cegah kehilangan akses superadmin)
        return $currentUser->id !== $targetUser->id;
    }

    public function toggleActive(User $currentUser, User $targetUser): bool
    {
        if (! $currentUser->isSuperadmin()) {
            return false;
        }

        // Tidak boleh menonaktifkan akunnya sendiri
        return $currentUser->id !== $targetUser->id;
    }
}
