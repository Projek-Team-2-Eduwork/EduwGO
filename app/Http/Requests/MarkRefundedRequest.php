<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkRefundedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('adminUpdate', $this->route('payment')->booking);
    }

    public function rules(): array
    {
        return [];
    }
}
