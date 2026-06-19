<?php

declare(strict_types=1);

namespace App\Support;

final class LocationResolver
{
    /**
     * @var array<int, array{name: string, region: string, country: string, lat: float, lng: float}>
     */
    private static array $cities = [];

    /**
     * Resolve a lat/lng pair to a human-readable "City, Country" string.
     *
     * Uses squared Euclidean distance on lat/lng to find the nearest anchor —
     * no sqrt needed because we only compare relative distances.
     */
    public static function resolve(float $lat, float $lng): string
    {
        $cities = self::cities();

        $bestIndex = 0;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($cities as $index => $city) {
            $dLat = $lat - $city['lat'];
            $dLng = $lng - $city['lng'];
            $distance = $dLat * $dLat + $dLng * $dLng;

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestIndex = $index;
            }
        }

        $nearest = $cities[$bestIndex];

        return $nearest['name'].', '.$nearest['country'];
    }

    /**
     * Return the full city list.
     *
     * Exposes the dataset so other consumers (e.g. the location filter in task 4)
     * can reuse the single authoritative source without re-loading the config.
     *
     * @return array<int, array{name: string, region: string, country: string, lat: float, lng: float}>
     */
    public static function cities(): array
    {
        if (self::$cities === []) {
            /** @var array<int, array{name: string, region: string, country: string, lat: float, lng: float}> $loaded */
            $loaded = require dirname(__DIR__, 2).'/config/cities.php';
            self::$cities = $loaded;
        }

        return self::$cities;
    }
}
