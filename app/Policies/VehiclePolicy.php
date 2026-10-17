<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->canManageBranch($vehicle->branch_id);
    }

    /** Admin cabang harus sudah punya cabang; superadmin bebas. */
    public function create(User $user): bool
    {
        return $user->isSuperadmin() || ($user->isBranchAdmin() && $user->branch_id !== null);
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->canManageBranch($vehicle->branch_id);
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->canManageBranch($vehicle->branch_id);
    }
}
