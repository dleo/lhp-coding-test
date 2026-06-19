<?php

declare(strict_types=1);

use App\Models\Attendee;
use App\Models\Event;
use App\Models\User;
use App\Notifications\AttendeeRegistered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('registers an attendee and queues a confirmation notification', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create(['status' => 'published']);

    $this->post(route('events.attendees.store', $event), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ])->assertRedirect();

    $attendee = Attendee::where('event_id', $event->id)->where('email', 'jane@example.com')->first();

    expect($attendee)->not->toBeNull();
    expect($attendee->name)->toBe('Jane Doe');
    expect($attendee->status)->toBe('registered');

    Notification::assertSentTo($attendee, AttendeeRegistered::class);
});

it('returns validation errors for a missing or invalid email', function (): void {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create(['status' => 'published']);

    $this->post(route('events.attendees.store', $event), [
        'name' => 'Jane Doe',
        'email' => 'not-an-email',
    ])->assertSessionHasErrors(['email']);

    $this->post(route('events.attendees.store', $event), [
        'name' => 'Jane Doe',
    ])->assertSessionHasErrors(['email']);
});

it('does not create a duplicate attendee or send a second notification on re-submit', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create(['status' => 'published']);

    $payload = ['name' => 'Jane Doe', 'email' => 'jane@example.com'];

    $this->post(route('events.attendees.store', $event), $payload)->assertRedirect();
    $this->post(route('events.attendees.store', $event), $payload)->assertRedirect();

    expect(Attendee::where('event_id', $event->id)->where('email', 'jane@example.com')->count())->toBe(1);

    Notification::assertSentTimes(AttendeeRegistered::class, 1);
});
