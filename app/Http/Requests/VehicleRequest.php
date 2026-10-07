<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorisasi ditangani Controller via Gate
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('plate_number')) {
            $cleaned = preg_replace('/[^A-Za-z0-9 ]/', '', (string) $this->plate_number);
            $cleaned = trim(preg_replace('/\s+/', ' ', $cleaned));
            $this->merge(['plate_number' => Str::upper($cleaned)]);
        }

        if ($this->has('is_active')) {
            $this->merge(['is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    public function rules(): array
    {
        $vehicleId = $this->route('kendaraan') ? $this->route('kendaraan')->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'vehicle_type_id' => ['required', 'exists:vehicle_types,id'],
            'tank_capacity' => ['nullable', 'numeric', 'min:0'],
            'plate_number' => ['required', 'string', 'max:20', 'unique:vehicles,plate_number,'.$vehicleId],
            'price_per_day' => ['required', 'numeric', 'min:0'],
            'image' => [
                $vehicleId ? 'nullable' : 'required',
                'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048',
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
