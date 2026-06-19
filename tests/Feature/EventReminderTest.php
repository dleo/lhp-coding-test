<?php

declare(strict_types=1);

use App\Models\Attendee;
use App\Models\Event;
use App\Models\User;
use App\Notifications\EventReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('sends the 3-day reminder and stamps reminder_3d_sent_at', function (): void {
    Notification::fake();

    $now = now();
    $this->travelTo($now);

    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => $now->copy()->addHours(72)->getTimestamp(),
    ]);

    $attendee = Attendee::create([
        'event_id' => $event->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'status' => 'registered',
    ]);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Notification::assertSentTo($attendee, EventReminder::class);

    expect($attendee->fresh()->reminder_3d_sent_at)->not->toBeNull();
    expect($attendee->fresh()->reminder_24h_sent_at)->toBeNull();
});

it('sends the 24-hour reminder and stamps reminder_24h_sent_at', function (): void {
    Notification::fake();

    $now = now();
    $this->travelTo($now);

    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => $now->copy()->addHours(24)->getTimestamp(),
    ]);

    $attendee = Attendee::create([
        'event_id' => $event->id,
        'name' => 'John Smith',
        'email' => 'john@example.com',
        'status' => 'registered',
    ]);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Notification::assertSentTo($attendee, EventReminder::class);

    expect($attendee->fresh()->reminder_24h_sent_at)->not->toBeNull();
    expect($attendee->fresh()->reminder_3d_sent_at)->toBeNull();
});

it('does not send the 3-day reminder twice (idempotent)', function (): void {
    Notification::fake();

    $now = now();
    $this->travelTo($now);

    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => $now->copy()->addHours(72)->getTimestamp(),
    ]);

    Attendee::create([
        'event_id' => $event->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'status' => 'registered',
    ]);

    $this->artisan('events:send-reminders')->assertSuccessful();
    $this->artisan('events:send-reminders')->assertSuccessful();

    Notification::assertSentTimes(EventReminder::class, 1);
});

it('does not send the 24-hour reminder twice (idempotent)', function (): void {
    Notification::fake();

    $now = now();
    $this->travelTo($now);

    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => $now->copy()->addHours(24)->getTimestamp(),
    ]);

    Attendee::create([
        'event_id' => $event->id,
        'name' => 'John Smith',
        'email' => 'john@example.com',
        'status' => 'registered',
    ]);

    $this->artisan('events:send-reminders')->assertSuccessful();
    $this->artisan('events:send-reminders')->assertSuccessful();

    Notification::assertSentTimes(EventReminder::class, 1);
});
