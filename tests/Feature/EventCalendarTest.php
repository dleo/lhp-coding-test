<?php

use App\Models\Event;
use App\Models\User;
use App\Support\LocationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the calendar endpoint with month and counts keys and stats', function () {
    $user = User::factory()->create();
    // 2024-03-15 00:00:00 UTC → 1710460800
    Event::factory()->for($user)->create(['created_time' => 1_710_460_800, 'status' => 'published']);

    $this->getJson(route('events.calendar', ['month' => '2024-03']))
        ->assertOk()
        ->assertJsonStructure([
            'month',
            'counts',
            'stats' => ['ms', 'bytes'],
        ])
        ->assertJsonPath('month', '2024-03');
});

it('returns per-day counts for events in the requested month', function () {
    $user = User::factory()->create();

    // 2024-03-15 00:00:00 UTC → 1710460800
    Event::factory()->for($user)->count(2)->create(['created_time' => 1_710_460_800, 'status' => 'published']);

    // 2024-03-20 00:00:00 UTC → 1710892800
    Event::factory()->for($user)->create(['created_time' => 1_710_892_800, 'status' => 'published']);

    // 2024-04-01 00:00:00 UTC → 1711929600 — outside month, must not appear
    Event::factory()->for($user)->create(['created_time' => 1_711_929_600, 'status' => 'published']);

    $response = $this->getJson(route('events.calendar', ['month' => '2024-03']))
        ->assertOk()
        ->assertJsonPath('month', '2024-03');

    $counts = $response->json('counts');

    expect($counts)->toBeArray()
        ->and($counts['2024-03-15'])->toBe(2)
        ->and($counts['2024-03-20'])->toBe(1)
        ->and($counts)->not->toHaveKey('2024-04-01');
});

it('excludes events outside the requested month', function () {
    $user = User::factory()->create();

    // Only a February event; querying March should return empty counts
    // 2024-02-14 00:00:00 UTC → 1707868800
    Event::factory()->for($user)->create(['created_time' => 1_707_868_800, 'status' => 'published']);

    $response = $this->getJson(route('events.calendar', ['month' => '2024-03']))
        ->assertOk();

    expect($response->json('counts'))->toBeEmpty();
});

it('applies the status filter to calendar counts', function () {
    $user = User::factory()->create();

    // 2024-03-15 00:00:00 UTC → 1710460800
    Event::factory()->for($user)->create(['created_time' => 1_710_460_800, 'status' => 'published']);
    Event::factory()->for($user)->create(['created_time' => 1_710_460_800, 'status' => 'cancelled']);

    $response = $this->getJson(route('events.calendar', ['month' => '2024-03', 'status' => 'published']))
        ->assertOk();

    $counts = $response->json('counts');

    // Only the published event counted — total on that day must be 1
    expect($counts['2024-03-15'])->toBe(1);
});

it('applies the city bbox filter to calendar counts', function () {
    $user = User::factory()->create();
    $cities = LocationResolver::cities();
    $newYork = $cities[0]; // lat 40.7128, lng -74.0060

    // 2024-03-15 00:00:00 UTC → 1710460800, near New York
    Event::factory()->for($user)->create([
        'created_time' => 1_710_460_800,
        'status' => 'published',
        'latitude' => $newYork['lat'] + 0.3,
        'longitude' => $newYork['lng'] + 0.3,
    ]);

    // Same day but far away (London anchor, index 37)
    $london = $cities[37];
    Event::factory()->for($user)->create([
        'created_time' => 1_710_460_800,
        'status' => 'published',
        'latitude' => $london['lat'],
        'longitude' => $london['lng'],
    ]);

    $response = $this->getJson(route('events.calendar', ['month' => '2024-03', 'city' => '0']))
        ->assertOk();

    $counts = $response->json('counts');

    // Only the New York event counted
    expect($counts['2024-03-15'])->toBe(1);
});

it('defaults to the current month when month param is absent', function () {
    $this->getJson(route('events.calendar'))
        ->assertOk()
        ->assertJsonPath('month', date('Y-m'));
});

it('defaults to the current month when month param is invalid', function () {
    $this->getJson(route('events.calendar', ['month' => 'not-a-month']))
        ->assertOk()
        ->assertJsonPath('month', date('Y-m'));
});

it('returns empty counts when no events exist for the month', function () {
    $this->getJson(route('events.calendar', ['month' => '2024-03']))
        ->assertOk()
        ->assertJsonPath('month', '2024-03')
        ->assertJsonPath('counts', []);
});
