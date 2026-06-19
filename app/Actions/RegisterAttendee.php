<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Attendee;
use App\Models\Event;
use App\Notifications\AttendeeRegistered;

final class RegisterAttendee
{
    /**
     * @param  array{name: string, email: string}  $attributes
     */
    public function handle(Event $event, array $attributes): Attendee
    {
        $attendee = $event->attendees()->firstOrCreate(
            ['email' => $attributes['email']],
            ['name' => $attributes['name'], 'status' => 'registered'],
        );

        if ($attendee->wasRecentlyCreated) {
            $attendee->notify(new AttendeeRegistered($event));
        }

        return $attendee;
    }
}
