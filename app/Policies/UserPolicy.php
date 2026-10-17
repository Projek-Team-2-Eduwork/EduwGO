<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function setRole(User $currentUser, User $targetUser, string $role): bool
    {
        if (! $currentUser->isSuperadmin()) {
            return false;
        }

        return $currentUser->id !== $targetUser->id;
    }

    public function toggleActive(User $currentUser, User $targetUser): bool
    {
        if (! $currentUser->isSuperadmin()) {
            return false;
        }

        return $currentUser->id !== $targetUser->id;
    }
}
