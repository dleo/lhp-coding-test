<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

final class Attendee extends Model
{
    use HasUuids, Notifiable;

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'reminder_3d_sent_at' => 'datetime',
        'reminder_24h_sent_at' => 'datetime',
    ];

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
