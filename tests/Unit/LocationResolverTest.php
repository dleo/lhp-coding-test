<?php

declare(strict_types=1);

use App\Support\LocationResolver;

describe('LocationResolver', function (): void {

    it('resolves an exact anchor coordinate to the correct city', function (): void {
        // New York anchor: [40.7128, -74.0060]
        expect(LocationResolver::resolve(40.7128, -74.0060))->toBe('New York, USA');
    });

    it('resolves a jittered coordinate near New York to New York', function (): void {
        // ±0.4° jitter — still closer to New York than any other anchor
        expect(LocationResolver::resolve(40.7128 + 0.4, -74.0060 + 0.4))->toBe('New York, USA');
        expect(LocationResolver::resolve(40.7128 - 0.4, -74.0060 - 0.4))->toBe('New York, USA');
    });

    it('resolves a coordinate near London to London', function (): void {
        // London anchor: [51.5074, -0.1278]
        expect(LocationResolver::resolve(51.5074, -0.1278))->toBe('London, UK');
    });

    it('resolves a jittered coordinate near London to London', function (): void {
        expect(LocationResolver::resolve(51.5074 + 0.4, -0.1278 + 0.4))->toBe('London, UK');
        expect(LocationResolver::resolve(51.5074 - 0.4, -0.1278 - 0.4))->toBe('London, UK');
    });

    it('resolves a coordinate near Tokyo to Tokyo', function (): void {
        // Tokyo anchor: [35.6762, 139.6503]
        expect(LocationResolver::resolve(35.6762, 139.6503))->toBe('Tokyo, Japan');
    });

    it('resolves a jittered coordinate near Tokyo to Tokyo', function (): void {
        expect(LocationResolver::resolve(35.6762 + 0.4, 139.6503 - 0.4))->toBe('Tokyo, Japan');
    });

    it('resolves a coordinate near Sydney to Sydney', function (): void {
        // Sydney anchor: [-33.8688, 151.2093]
        expect(LocationResolver::resolve(-33.8688, 151.2093))->toBe('Sydney, Australia');
    });

    it('resolves a jittered coordinate near Sydney to Sydney', function (): void {
        expect(LocationResolver::resolve(-33.8688 + 0.4, 151.2093 + 0.4))->toBe('Sydney, Australia');
        expect(LocationResolver::resolve(-33.8688 - 0.4, 151.2093 - 0.4))->toBe('Sydney, Australia');
    });

    it('resolves a coordinate near San Francisco to San Francisco', function (): void {
        // SF anchor: [37.7749, -122.4194]
        expect(LocationResolver::resolve(37.7749, -122.4194))->toBe('San Francisco, USA');
    });

    it('resolves a jittered coordinate near San Francisco to San Francisco', function (): void {
        expect(LocationResolver::resolve(37.7749 + 0.4, -122.4194 + 0.4))->toBe('San Francisco, USA');
        expect(LocationResolver::resolve(37.7749 - 0.4, -122.4194 - 0.4))->toBe('San Francisco, USA');
    });

    it('resolves a coordinate near Dubai to Dubai', function (): void {
        // Dubai anchor: [25.2048, 55.2708]
        expect(LocationResolver::resolve(25.2048, 55.2708))->toBe('Dubai, UAE');
    });

    it('resolves a coordinate near São Paulo to São Paulo', function (): void {
        // São Paulo anchor: [-23.5505, -46.6333]
        expect(LocationResolver::resolve(-23.5505, -46.6333))->toBe('São Paulo, Brazil');
    });

    it('resolves a coordinate near Singapore to Singapore', function (): void {
        // Singapore anchor: [1.3521, 103.8198]
        expect(LocationResolver::resolve(1.3521, 103.8198))->toBe('Singapore, Singapore');
    });

    it('is deterministic — repeated calls with the same coordinates return the same result', function (): void {
        $lat = 48.8566;
        $lng = 2.3522;

        $first = LocationResolver::resolve($lat, $lng);
        $second = LocationResolver::resolve($lat, $lng);

        expect($first)->toBe($second)->toBe('Paris, France');
    });

    it('exposes the cities list for reuse', function (): void {
        $cities = LocationResolver::cities();

        expect($cities)->toBeArray()->not->toBeEmpty();

        // Every entry must have the required keys and correct types
        foreach ($cities as $city) {
            expect($city)->toHaveKeys(['name', 'region', 'country', 'lat', 'lng']);
            expect($city['name'])->toBeString()->not->toBeEmpty();
            expect($city['country'])->toBeString()->not->toBeEmpty();
            expect($city['lat'])->toBeFloat();
            expect($city['lng'])->toBeFloat();
        }
    });

    it('covers all seeder anchor cities — 75 total entries', function (): void {
        // EventSeeder::CITY_ANCHORS has 75 entries (22 US + 8 CA + 7 MX + 30 EU + 8 global).
        // config/cities.php must have one entry per anchor so nearest-match works.
        expect(count(LocationResolver::cities()))->toBeGreaterThanOrEqual(75);
    });

});
