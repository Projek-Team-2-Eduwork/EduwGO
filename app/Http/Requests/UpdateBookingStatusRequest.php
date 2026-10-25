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
            // Lunas hanya lewat pembayaran (tunai via payCash atau webhook/sync Xendit)
            'to' => ['required', Rule::enum(BookingStatus::class), Rule::notIn([BookingStatus::Paid->value])],
            'note' => ['nullable', 'string', 'max:500'],
            'reason' => ['required_if:to,cancelled', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'to.not_in' => 'Status Lunas hanya bisa diset lewat Catat Bayar Tunai atau pembayaran Xendit.',
            'reason.required_if' => 'Alasan pembatalan wajib diisi jika pesanan dibatalkan.',
        ];
    }
}
