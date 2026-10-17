<?php

namespace App\Http\Requests;

class BranchProfileRequest extends BranchRequest
{
    public function rules(): array
    {
        return array_diff_key(parent::rules(), array_flip(['name', 'is_active']));
    }
}
