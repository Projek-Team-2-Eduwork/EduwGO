<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    /** Master cabang dikelola terpusat oleh superadmin. */
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Admin cabang boleh mengubah profil cabangnya sendiri (alamat, kontak, jam, sosmed);
     * nama, slug, dan status aktif tetap urusan superadmin.
     */
    public function updateProfile(User $user, Branch $branch): bool
    {
        return $user->canManageBranch($branch->id);
    }
}
