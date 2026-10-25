<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('start')) && validator(['start' => $this->input('start')], ['start' => ['required', 'date']])->passes()) {
            try {
                $this->merge(['start' => Carbon::parse($this->input('start'), config('app.timezone'))
                    ->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')]);
            } catch (\Exception) {
                // Input tidak valid tetap ditangani aturan date.
            }
        }
    }

    public function rules(): array
    {
        return [
            'start' => ['required', 'date', 'after_or_equal:now'],
            'days' => ['required', 'integer', 'min:1', 'max:'.setting('booking.max_days', 5)],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
