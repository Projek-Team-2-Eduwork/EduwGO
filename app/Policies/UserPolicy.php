<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Role hanya bisa diubah untuk staf: role penyewa tidak diubah lewat panel admin.
     */
    public function setRole(User $currentUser, User $targetUser, string $role): bool
    {
        return $this->managesStaff($currentUser, $targetUser);
    }

    public function sendResetLink(User $currentUser, User $targetUser): bool
    {
        return $this->managesStaff($currentUser, $targetUser);
    }

    public function toggleActive(User $currentUser, User $targetUser): bool
    {
        if (! $currentUser->isSuperadmin()) {
            return false;
        }

        return $currentUser->id !== $targetUser->id;
    }

    private function managesStaff(User $currentUser, User $targetUser): bool
    {
        return $currentUser->isSuperadmin() && $currentUser->id !== $targetUser->id && $targetUser->isStaff();
    }
}
