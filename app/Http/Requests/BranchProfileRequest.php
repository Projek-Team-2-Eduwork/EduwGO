<?php

namespace App\Http\Requests;

/**
 * Profil cabang yang boleh diubah admin cabang: tanpa nama & status aktif (urusan superadmin).
 */
class BranchProfileRequest extends BranchRequest
{
    public function rules(): array
    {
        return array_diff_key(parent::rules(), array_flip(['name', 'is_active']));
    }
}
