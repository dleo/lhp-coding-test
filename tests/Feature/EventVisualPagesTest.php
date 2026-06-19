<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders Events/VisualOne with listing scaffold props', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('events.visual1'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Events/VisualOne')
                ->has('cities')
                ->has('statuses')
                ->has('filters')
        );
});

it('renders Events/VisualTwo with listing scaffold props', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('events.visual2'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Events/VisualTwo')
                ->has('cities')
                ->has('statuses')
                ->has('filters')
        );
});
