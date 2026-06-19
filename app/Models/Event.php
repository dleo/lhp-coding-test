<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\EventImageResolver;
use App\Support\LocationResolver;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    /** @var list<string> */
    protected $appends = ['location_name', 'images'];

    protected $casts = [
        'payload' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getLocationNameAttribute(): string
    {
        return LocationResolver::resolve(
            (float) $this->latitude,
            (float) $this->longitude,
        );
    }

    /**
     * @return array<int, string>
     */
    public function getImagesAttribute(): array
    {
        return EventImageResolver::for((string) $this->id, (string) $this->type);
    }
}
