<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class VehicleFilterRequest extends FormRequest
{
    private const FILTER_KEYS = ['type', 'start_at', 'days', 'available', 'q'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Batas paling awal waktu mulai: sekarang dibulatkan ke atas ke jam penuh.
     */
    public static function earliestStart(): Carbon
    {
        return now()->ceilHour();
    }

    /**
     * Tanpa query filter sama sekali (mis. kembali dari detail), pakai filter tersimpan di session.
     */
    protected function prepareForValidation(): void
    {
        if ($this->hasAny(self::FILTER_KEYS)) {
            return;
        }

        $saved = array_filter((array) $this->session()->get('booking.filter', []), fn ($value) => filled($value));

        // Waktu mulai tersimpan yang sudah lewat dibuang supaya tidak memicu error validasi berulang.
        if (isset($saved['start_at']) && Carbon::parse($saved['start_at'])->lt(self::earliestStart())) {
            unset($saved['start_at'], $saved['days']);
        }

        $this->merge($saved);
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'integer', 'exists:vehicle_types,id'],
            'start_at' => ['nullable', 'date', 'required_with:days', 'after_or_equal:'.self::earliestStart()->toDateTimeString()],
            'days' => ['nullable', 'integer', 'required_with:start_at', 'min:1', 'max:'.(int) setting('booking.max_days', 5)],
            'available' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.exists' => 'Tipe kendaraan tidak valid.',
            'start_at.date' => 'Tanggal & jam mulai tidak valid.',
            'start_at.required_with' => 'Isi tanggal & jam mulai sewa.',
            'start_at.after_or_equal' => 'Waktu mulai tidak boleh di masa lalu.',
            'days.integer' => 'Durasi tidak valid.',
            'days.required_with' => 'Pilih durasi sewa.',
            'days.min' => 'Durasi minimal 1 hari.',
            'days.max' => 'Durasi melebihi batas maksimal sewa.',
            'q.max' => 'Kata kunci pencarian terlalu panjang.',
        ];
    }

    /**
     * Waktu mulai sebagai Carbon (null bila filter waktu belum diisi).
     */
    public function startAt(): ?Carbon
    {
        return $this->filled('start_at') ? Carbon::parse($this->input('start_at')) : null;
    }
}
