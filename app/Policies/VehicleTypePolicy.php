<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleType;

class VehicleTypePolicy
{
    /** Master tipe berlaku lintas cabang, jadi dikelola superadmin saja. */
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
