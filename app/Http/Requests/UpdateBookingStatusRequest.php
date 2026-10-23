<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to' => ['required', Rule::enum(BookingStatus::class)],
            'note' => ['nullable', 'string', 'max:500'],
            'reason' => ['required_if:to,cancelled', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required_if' => 'Alasan pembatalan wajib diisi jika pesanan dibatalkan.',
        ];
    }
}
