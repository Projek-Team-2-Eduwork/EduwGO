<?php

namespace App\Http\Requests;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorisasi ditangani Controller via Gate
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'operating_hours' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'social_links' => ['nullable', 'array'],
        ];

        foreach (array_keys(Branch::SOCIALS) as $key) {
            $rules["social_links.$key"] = ['nullable', 'url:http,https', 'max:255'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Nomor kontak hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
            'social_links.*.url' => 'Link media sosial harus berupa URL lengkap (https://...).',
        ];
    }

    /**
     * Data tervalidasi dengan link sosmed kosong dibuang.
     *
     * @return array<string, mixed>
     */
    public function branchData(): array
    {
        $data = $this->validated();
        $data['social_links'] = array_filter($data['social_links'] ?? [], fn ($url) => filled($url)) ?: null;

        return $data;
    }
}
