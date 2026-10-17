<?php

namespace App\Policies;

use App\Models\User;

class SettingPolicy
{
    /** Pengaturan toko global (brand, aturan sewa, S&K): superadmin. Admin cabang edit profil cabangnya sendiri. */
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }
}
