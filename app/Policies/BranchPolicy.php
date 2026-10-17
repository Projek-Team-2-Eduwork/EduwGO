<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
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

    public function updateProfile(User $user, Branch $branch): bool
    {
        return $user->canManageBranch($branch->id);
    }
}
