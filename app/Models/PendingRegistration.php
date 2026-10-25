<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

#[Fillable(['email', 'name', 'phone', 'password', 'code_hash', 'expires_at', 'attempts', 'last_sent_at'])]
#[Hidden(['password', 'code_hash', 'session_token_hash'])]
class PendingRegistration extends Model
{
    use MassPrunable, Notifiable;

    /** Data pendaftaran disimpan paling lama sekian jam sejak kode terakhir dikirim. */
    public const TTL_HOURS = 24;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * Pendaftaran yang terbengkalai, dibersihkan oleh model:prune dan saat ada pendaftaran baru.
     */
    public function prunable(): Builder
    {
        return static::where('last_sent_at', '<', now()->subHours(self::TTL_HOURS));
    }
}
