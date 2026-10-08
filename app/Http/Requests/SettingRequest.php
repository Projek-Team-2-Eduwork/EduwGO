<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi di-handle oleh Gate di Controller
    }

    public function rules(): array
    {
        return [
            'brand_name' => ['required', 'string', 'max:80'],
            'brand_tagline' => ['nullable', 'string', 'max:255'],
            'brand_primary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/i'],
            'brand_accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/i'],

            'brand_logo_light' => ['nullable', 'image', 'mimes:png,svg,ico,jpg', 'max:512'],
            'brand_logo_dark' => ['nullable', 'image', 'mimes:png,svg,ico,jpg', 'max:512'],
            'brand_favicon' => ['nullable', 'file', 'mimes:png,svg,ico,jpg', 'max:512'],

            'contact_whatsapp' => ['required', 'string', 'regex:/^62\d{9,13}$/'],
            'contact_address' => ['nullable', 'string', 'max:500'],
            'contact_hours' => ['nullable', 'string', 'max:255'],

            'social_instagram' => ['nullable', 'string', 'max:255'],
            'social_facebook' => ['nullable', 'string', 'max:255'],
            'social_twitter' => ['nullable', 'string', 'max:255'],

            'booking_max_days' => ['required', 'integer', 'min:1', 'max:14'],
            'booking_invoice_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'booking_buffer_minutes' => ['required', 'integer', 'min:0', 'max:720'],

            'content_terms' => ['nullable', 'string'],
        ];
    }
}
