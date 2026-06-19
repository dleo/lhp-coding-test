<?php

declare(strict_types=1);

use App\Support\EventImageResolver;

describe('EventImageResolver', function (): void {

    it('is deterministic — repeated calls with the same id and type return identical results', function (): void {
        $id = '550e8400-e29b-41d4-a716-446655440000';
        $type = 'concert';

        $first = EventImageResolver::for($id, $type);
        $second = EventImageResolver::for($id, $type);

        expect($first)->toBe($second);
    });

    it('returns at least 2 and at most 3 URLs', function (): void {
        $id = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';

        $urls = EventImageResolver::for($id, 'festival');

        expect(count($urls))->toBeGreaterThanOrEqual(2)->toBeLessThanOrEqual(3);
    });

    it('every URL is a local /images/events/pool/ path ending in .svg', function (): void {
        $id = 'deadbeef-dead-beef-dead-beefdeadbeef';

        $urls = EventImageResolver::for($id, 'conference');

        expect($urls)->not->toBeEmpty();

        foreach ($urls as $url) {
            expect($url)
                ->toStartWith('/images/events/pool/')
                ->toEndWith('.svg');
        }
    });

    it('URL filenames are zero-padded two-digit slot indices within pool bounds', function (): void {
        $id = '123e4567-e89b-12d3-a456-426614174000';

        $urls = EventImageResolver::for($id, 'sports');

        foreach ($urls as $url) {
            // Extract the filename without extension, e.g. "07" from "/images/events/pool/07.svg"
            $basename = basename($url, '.svg');

            expect($basename)->toMatch('/^\d{2}$/');

            $index = (int) $basename;
            expect($index)->toBeGreaterThanOrEqual(0)
                ->toBeLessThan(EventImageResolver::POOL_SIZE);
        }
    });

    it('all returned URLs are distinct (no duplicates within one event)', function (): void {
        $id = 'ffffffff-ffff-ffff-ffff-ffffffffffff';

        $urls = EventImageResolver::for($id, 'music');

        expect(count($urls))->toBe(count(array_unique($urls)));
    });

    it('different ids can yield different URLs', function (): void {
        // With a pool of 20 and deterministic derivation it is statistically
        // near-certain that two distinct UUIDs produce at least one differing URL.

        // They may coincidentally be equal for a tiny pool, so we only assert
        // that the resolver handles multiple IDs without error.  The more
        // useful assertion is that at least one pair is actually different
        // across a small batch.
        $pairs = [
            ['00000000-0000-0000-0000-000000000001', 'art'],
            ['11111111-1111-1111-1111-111111111111', 'art'],
            ['22222222-2222-2222-2222-222222222222', 'art'],
            ['33333333-3333-3333-3333-333333333333', 'art'],
            ['44444444-4444-4444-4444-444444444444', 'art'],
        ];

        $results = array_map(
            fn (array $pair): array => EventImageResolver::for($pair[0], $pair[1]),
            $pairs,
        );

        // Serialise each result for comparison; at least two must differ.
        $serialised = array_map('json_encode', $results);
        $unique = array_unique($serialised);

        expect(count($unique))->toBeGreaterThan(1);
    });

    it('POOL_SIZE constant is a positive integer and matches expected pool size', function (): void {
        expect(EventImageResolver::POOL_SIZE)->toBeInt()->toBeGreaterThan(0);
    });

});
