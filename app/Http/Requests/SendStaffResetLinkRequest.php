<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendStaffResetLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sendResetLink', $this->route('pengguna'));
    }

    public function rules(): array
    {
        return [];
    }
}
