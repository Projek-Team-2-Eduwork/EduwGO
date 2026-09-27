<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],

            // Nomor WhatsApp aktif, dipakai saat checkout & dicek admin.
            'phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s()]{8,20}$/'],

            // Username sosmed, boleh diawali @ (dihapus otomatis sebelum disimpan).
            'instagram' => ['nullable', 'string', 'regex:/^@?[A-Za-z0-9._]{1,50}$/'],
            'facebook' => ['nullable', 'string', 'regex:/^@?[A-Za-z0-9._]{1,50}$/'],

            // Preferensi tema user.
            'theme' => ['nullable', 'string', Rule::in(['light', 'dark', 'system'])],
        ];
    }
}
