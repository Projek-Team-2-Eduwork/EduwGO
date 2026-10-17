<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleType;

class VehicleTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, VehicleType $vehicleType): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, VehicleType $vehicleType): bool
    {
        return $user->isSuperadmin();
    }
}
