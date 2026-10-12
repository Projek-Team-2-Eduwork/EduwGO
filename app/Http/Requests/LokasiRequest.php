<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LokasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorisasi ditangani Controller via Gate
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'operating_hours' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
