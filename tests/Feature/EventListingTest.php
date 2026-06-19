<?php

use App\Models\Event;
use App\Models\User;
use App\Support\LocationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the events listing shell without authentication', function () {
    $this->get(route('events.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Index')
            ->has('statuses', 4)
            ->has('cities')
            ->where('filters.from', '2023-01-01')
            ->where('filters.to', '')
            ->where('filters.city', '')
        );
});

it('returns a json page of events with load stats for lazy loading', function () {
    $user = User::factory()->create(['name' => 'Ada Lovelace']);
    Event::factory()->for($user)->create([
        'type' => 'concert',
        'status' => 'published',
        'created_time' => 1_700_000_000,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
    ]);

    $this->getJson(route('events.data'))
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'total',
            'stats' => ['ms', 'bytes'],
        ])
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.type', 'concert')
        ->assertJsonPath('data.0.created_time', 1_700_000_000)
        ->assertJsonPath('data.0.latitude', 40.7128)
        ->assertJsonPath('data.0.user.name', 'Ada Lovelace');
});

it('filters the data endpoint by status', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['status' => 'published']);
    Event::factory()->for($user)->create(['status' => 'cancelled']);

    $this->getJson(route('events.data', ['status' => 'cancelled']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.status', 'cancelled');
});

it('shows an event detail page with its payload', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'payload' => ['name' => 'Global Tech Summit', 'location' => ['lat' => 1.5, 'lng' => 2.5]],
    ]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Show')
            ->where('event.id', $event->id)
            ->where('event.payload.name', 'Global Tech Summit')
        );
});

it('renders the two visualization pages and the dashboard without authentication', function () {
    $this->get(route('events.visual1'))->assertOk();
    $this->get(route('events.visual2'))->assertOk();
    $this->get(route('dashboard'))->assertOk();
});

it('filters the data endpoint by from date (unix range on created_time)', function () {
    $user = User::factory()->create();

    // Event before the window: 2022-06-01 00:00:00 UTC → 1654041600
    Event::factory()->for($user)->create(['created_time' => 1_654_041_600, 'status' => 'published']);

    // Event inside the window: 2024-03-15 00:00:00 UTC → 1710460800
    Event::factory()->for($user)->create(['created_time' => 1_710_460_800, 'status' => 'published']);

    $this->getJson(route('events.data', ['from' => '2023-01-01']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.created_time', 1_710_460_800);
});

it('filters the data endpoint by to date (unix range on created_time)', function () {
    $user = User::factory()->create();

    // Event inside the window: 2022-06-01 00:00:00 UTC
    Event::factory()->for($user)->create(['created_time' => 1_654_041_600, 'status' => 'published']);

    // Event after the window: 2024-03-15 00:00:00 UTC
    Event::factory()->for($user)->create(['created_time' => 1_710_460_800, 'status' => 'published']);

    $this->getJson(route('events.data', ['to' => '2023-01-01']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.created_time', 1_654_041_600);
});

it('filters the data endpoint by from and to date range', function () {
    $user = User::factory()->create();

    // Before range: 2021-01-01 UTC → 1609459200
    Event::factory()->for($user)->create(['created_time' => 1_609_459_200]);
    // Inside range: 2023-06-01 UTC → 1685577600
    Event::factory()->for($user)->create(['created_time' => 1_685_577_600]);
    // After range: 2025-01-01 UTC → 1735689600
    Event::factory()->for($user)->create(['created_time' => 1_735_689_600]);

    $this->getJson(route('events.data', ['from' => '2023-01-01', 'to' => '2024-01-01']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.created_time', 1_685_577_600);
});

it('filters the data endpoint by city using bounding-box on latitude and longitude', function () {
    $user = User::factory()->create();

    // Retrieve the first city from the authoritative list (index 0 = New York anchor)
    $cities = LocationResolver::cities();
    $newYork = $cities[0]; // lat 40.7128, lng -74.0060

    // Event near New York anchor (within ±0.6°)
    Event::factory()->for($user)->create([
        'latitude' => $newYork['lat'] + 0.3,
        'longitude' => $newYork['lng'] + 0.3,
    ]);

    // Event far from New York (London anchor, index 37)
    $london = $cities[37]; // lat 51.5074, lng -0.1278
    Event::factory()->for($user)->create([
        'latitude' => $london['lat'],
        'longitude' => $london['lng'],
    ]);

    $this->getJson(route('events.data', ['city' => '0']))
        ->assertOk()
        ->assertJsonPath('total', 1);
});

it('index shares the cities list from LocationResolver', function () {
    $cities = LocationResolver::cities();

    $this->get(route('events.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Index')
            ->has('cities', count($cities))
        );
});
