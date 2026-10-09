<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function setRole(User $currentUser, User $targetUser, string $role): bool
    {
        if (! $currentUser->hasRole('admin')) {
            return false;
        }
        // Tidak boleh mencabut role admin dari dirinya sendiri
        if ($currentUser->id === $targetUser->id && $role === 'user') {
            return false;
        }

        return true;
    }

    public function toggleActive(User $currentUser, User $targetUser): bool
    {
        if (! $currentUser->hasRole('admin')) {
            return false;
        }
        // Tidak boleh menonaktifkan akunnya sendiri
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        return true;
    }
}
