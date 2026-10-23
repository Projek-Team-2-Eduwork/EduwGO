<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CronRunRequest extends FormRequest
{
    /**
     * Perintah yang boleh dipicu lewat cron eksternal.
     * Default `schedule:run` (Laravel memilih jadwal yang jatuh tempo di routes/console.php).
     */
    public const TASKS = [
        'schedule:run',
        'bookings:expire',
        'bookings:flag-overdue',
        'bookings:remind',
    ];

    public function authorize(): bool
    {
        // Token sudah diverifikasi middleware VerifyCronToken
        return true;
    }

    public function rules(): array
    {
        return [
            'task' => ['nullable', 'string', Rule::in(self::TASKS)],
        ];
    }

    public function task(): string
    {
        return $this->validated('task') ?? 'schedule:run';
    }
}
