<?php

declare(strict_types=1);

/**
 * Static city dataset for offline nearest-city resolution.
 *
 * Every entry maps to one of the CITY_ANCHORS in EventSeeder (lat/lng must match
 * the seeder constant exactly so nearest-match resolves seeded events correctly).
 * Additional global hubs are included for completeness.
 *
 * This list is the single authoritative source used by LocationResolver and,
 * later, by the location filter (task 4) — do not duplicate it elsewhere.
 *
 * @return array<int, array{name: string, region: string, country: string, lat: float, lng: float}>
 */
return [
    // -------------------------------------------------------------------------
    // United States  (matches EventSeeder::CITY_ANCHORS rows 0–21)
    // -------------------------------------------------------------------------
    ['name' => 'New York',      'region' => 'NY', 'country' => 'USA', 'lat' => 40.7128,  'lng' => -74.0060],
    ['name' => 'Los Angeles',   'region' => 'CA', 'country' => 'USA', 'lat' => 34.0522,  'lng' => -118.2437],
    ['name' => 'Chicago',       'region' => 'IL', 'country' => 'USA', 'lat' => 41.8781,  'lng' => -87.6298],
    ['name' => 'Houston',       'region' => 'TX', 'country' => 'USA', 'lat' => 29.7604,  'lng' => -95.3698],
    ['name' => 'Phoenix',       'region' => 'AZ', 'country' => 'USA', 'lat' => 33.4484,  'lng' => -112.0740],
    ['name' => 'Philadelphia',  'region' => 'PA', 'country' => 'USA', 'lat' => 39.9526,  'lng' => -75.1652],
    ['name' => 'San Antonio',   'region' => 'TX', 'country' => 'USA', 'lat' => 29.4241,  'lng' => -98.4936],
    ['name' => 'San Diego',     'region' => 'CA', 'country' => 'USA', 'lat' => 32.7157,  'lng' => -117.1611],
    ['name' => 'Dallas',        'region' => 'TX', 'country' => 'USA', 'lat' => 32.7767,  'lng' => -96.7970],
    ['name' => 'San Jose',      'region' => 'CA', 'country' => 'USA', 'lat' => 37.3382,  'lng' => -121.8863],
    ['name' => 'Austin',        'region' => 'TX', 'country' => 'USA', 'lat' => 30.2672,  'lng' => -97.7431],
    ['name' => 'San Francisco', 'region' => 'CA', 'country' => 'USA', 'lat' => 37.7749,  'lng' => -122.4194],
    ['name' => 'Seattle',       'region' => 'WA', 'country' => 'USA', 'lat' => 47.6062,  'lng' => -122.3321],
    ['name' => 'Denver',        'region' => 'CO', 'country' => 'USA', 'lat' => 39.7392,  'lng' => -104.9903],
    ['name' => 'Boston',        'region' => 'MA', 'country' => 'USA', 'lat' => 42.3601,  'lng' => -71.0589],
    ['name' => 'Las Vegas',     'region' => 'NV', 'country' => 'USA', 'lat' => 36.1699,  'lng' => -115.1398],
    ['name' => 'Miami',         'region' => 'FL', 'country' => 'USA', 'lat' => 25.7617,  'lng' => -80.1918],
    ['name' => 'Atlanta',       'region' => 'GA', 'country' => 'USA', 'lat' => 33.7490,  'lng' => -84.3880],
    ['name' => 'Washington DC', 'region' => 'DC', 'country' => 'USA', 'lat' => 38.9072,  'lng' => -77.0369],
    ['name' => 'Nashville',     'region' => 'TN', 'country' => 'USA', 'lat' => 36.1627,  'lng' => -86.7816],
    ['name' => 'Portland',      'region' => 'OR', 'country' => 'USA', 'lat' => 45.5152,  'lng' => -122.6784],
    ['name' => 'New Orleans',   'region' => 'LA', 'country' => 'USA', 'lat' => 29.9511,  'lng' => -90.0715],

    // -------------------------------------------------------------------------
    // Canada  (matches EventSeeder::CITY_ANCHORS rows 22–29)
    // -------------------------------------------------------------------------
    ['name' => 'Toronto',   'region' => 'ON', 'country' => 'Canada', 'lat' => 43.6532, 'lng' => -79.3832],
    ['name' => 'Montreal',  'region' => 'QC', 'country' => 'Canada', 'lat' => 45.5019, 'lng' => -73.5674],
    ['name' => 'Vancouver', 'region' => 'BC', 'country' => 'Canada', 'lat' => 49.2827, 'lng' => -123.1207],
    ['name' => 'Calgary',   'region' => 'AB', 'country' => 'Canada', 'lat' => 51.0447, 'lng' => -114.0719],
    ['name' => 'Ottawa',    'region' => 'ON', 'country' => 'Canada', 'lat' => 45.4215, 'lng' => -75.6972],
    ['name' => 'Edmonton',  'region' => 'AB', 'country' => 'Canada', 'lat' => 53.5461, 'lng' => -113.4938],
    ['name' => 'Quebec City', 'region' => 'QC', 'country' => 'Canada', 'lat' => 46.8139, 'lng' => -71.2080],
    ['name' => 'Winnipeg',  'region' => 'MB', 'country' => 'Canada', 'lat' => 49.8951, 'lng' => -97.1384],

    // -------------------------------------------------------------------------
    // Mexico  (matches EventSeeder::CITY_ANCHORS rows 30–36)
    // -------------------------------------------------------------------------
    ['name' => 'Mexico City',   'region' => 'CDMX', 'country' => 'Mexico', 'lat' => 19.4326, 'lng' => -99.1332],
    ['name' => 'Guadalajara',   'region' => 'Jalisco', 'country' => 'Mexico', 'lat' => 20.6597, 'lng' => -103.3496],
    ['name' => 'Monterrey',     'region' => 'NL', 'country' => 'Mexico', 'lat' => 25.6866, 'lng' => -100.3161],
    ['name' => 'Puebla',        'region' => 'Puebla', 'country' => 'Mexico', 'lat' => 19.0414, 'lng' => -98.2063],
    ['name' => 'Tijuana',       'region' => 'BC', 'country' => 'Mexico', 'lat' => 32.5149, 'lng' => -117.0382],
    ['name' => 'Cancun',        'region' => 'QROO', 'country' => 'Mexico', 'lat' => 21.1619, 'lng' => -86.8515],
    ['name' => 'Merida',        'region' => 'Yucatan', 'country' => 'Mexico', 'lat' => 20.9674, 'lng' => -89.5926],

    // -------------------------------------------------------------------------
    // Europe  (matches EventSeeder::CITY_ANCHORS rows 37–66)
    // -------------------------------------------------------------------------
    ['name' => 'London',     'region' => 'England',  'country' => 'UK',          'lat' => 51.5074, 'lng' => -0.1278],
    ['name' => 'Paris',      'region' => 'Île-de-France', 'country' => 'France', 'lat' => 48.8566, 'lng' => 2.3522],
    ['name' => 'Berlin',     'region' => 'Berlin',   'country' => 'Germany',     'lat' => 52.5200, 'lng' => 13.4050],
    ['name' => 'Madrid',     'region' => 'Madrid',   'country' => 'Spain',       'lat' => 40.4168, 'lng' => -3.7038],
    ['name' => 'Rome',       'region' => 'Lazio',    'country' => 'Italy',       'lat' => 41.9028, 'lng' => 12.4964],
    ['name' => 'Amsterdam',  'region' => 'North Holland', 'country' => 'Netherlands', 'lat' => 52.3676, 'lng' => 4.9041],
    ['name' => 'Barcelona',  'region' => 'Catalonia', 'country' => 'Spain',      'lat' => 41.3851, 'lng' => 2.1734],
    ['name' => 'Munich',     'region' => 'Bavaria',  'country' => 'Germany',     'lat' => 48.1351, 'lng' => 11.5820],
    ['name' => 'Milan',      'region' => 'Lombardy', 'country' => 'Italy',       'lat' => 45.4642, 'lng' => 9.1900],
    ['name' => 'Vienna',     'region' => 'Vienna',   'country' => 'Austria',     'lat' => 48.2082, 'lng' => 16.3738],
    ['name' => 'Prague',     'region' => 'Bohemia',  'country' => 'Czech Republic', 'lat' => 50.0755, 'lng' => 14.4378],
    ['name' => 'Lisbon',     'region' => 'Lisboa',   'country' => 'Portugal',    'lat' => 38.7223, 'lng' => -9.1393],
    ['name' => 'Dublin',     'region' => 'Leinster', 'country' => 'Ireland',     'lat' => 53.3498, 'lng' => -6.2603],
    ['name' => 'Copenhagen', 'region' => 'Capital',  'country' => 'Denmark',     'lat' => 55.6761, 'lng' => 12.5683],
    ['name' => 'Stockholm',  'region' => 'Stockholm', 'country' => 'Sweden',     'lat' => 59.3293, 'lng' => 18.0686],
    ['name' => 'Oslo',       'region' => 'Viken',    'country' => 'Norway',      'lat' => 59.9139, 'lng' => 10.7522],
    ['name' => 'Helsinki',   'region' => 'Uusimaa',  'country' => 'Finland',     'lat' => 60.1699, 'lng' => 24.9384],
    ['name' => 'Brussels',   'region' => 'Brussels', 'country' => 'Belgium',     'lat' => 50.8503, 'lng' => 4.3517],
    ['name' => 'Zurich',     'region' => 'Zurich',   'country' => 'Switzerland', 'lat' => 47.3769, 'lng' => 8.5417],
    ['name' => 'Warsaw',     'region' => 'Masovian', 'country' => 'Poland',      'lat' => 52.2297, 'lng' => 21.0122],
    ['name' => 'Budapest',   'region' => 'Budapest', 'country' => 'Hungary',     'lat' => 47.4979, 'lng' => 19.0402],
    ['name' => 'Athens',     'region' => 'Attica',   'country' => 'Greece',      'lat' => 37.9838, 'lng' => 23.7275],
    ['name' => 'Lyon',       'region' => 'Auvergne-Rhône-Alpes', 'country' => 'France', 'lat' => 45.7640, 'lng' => 4.8357],
    ['name' => 'Hamburg',    'region' => 'Hamburg',  'country' => 'Germany',     'lat' => 53.5511, 'lng' => 9.9937],
    ['name' => 'Manchester', 'region' => 'England',  'country' => 'UK',          'lat' => 53.4808, 'lng' => -2.2426],
    ['name' => 'Edinburgh',  'region' => 'Scotland', 'country' => 'UK',          'lat' => 55.9533, 'lng' => -3.1883],
    ['name' => 'Frankfurt',  'region' => 'Hesse',    'country' => 'Germany',     'lat' => 50.1109, 'lng' => 8.6821],
    ['name' => 'Krakow',     'region' => 'Malopolska', 'country' => 'Poland',    'lat' => 50.0647, 'lng' => 19.9450],
    ['name' => 'Porto',      'region' => 'Norte',    'country' => 'Portugal',    'lat' => 41.1579, 'lng' => -8.6291],
    ['name' => 'Naples',     'region' => 'Campania', 'country' => 'Italy',       'lat' => 40.8518, 'lng' => 14.2681],

    // -------------------------------------------------------------------------
    // Global hubs  (matches EventSeeder::CITY_ANCHORS rows 67–74)
    // -------------------------------------------------------------------------
    ['name' => 'Tokyo',       'region' => 'Kanto',          'country' => 'Japan',       'lat' => 35.6762,  'lng' => 139.6503],
    ['name' => 'Seoul',       'region' => 'Seoul',          'country' => 'South Korea', 'lat' => 37.5665,  'lng' => 126.9780],
    ['name' => 'Singapore',   'region' => 'Central',        'country' => 'Singapore',   'lat' => 1.3521,   'lng' => 103.8198],
    ['name' => 'Sydney',      'region' => 'New South Wales', 'country' => 'Australia',  'lat' => -33.8688, 'lng' => 151.2093],
    ['name' => 'Melbourne',   'region' => 'Victoria',       'country' => 'Australia',   'lat' => -37.8136, 'lng' => 144.9631],
    ['name' => 'Dubai',       'region' => 'Dubai',          'country' => 'UAE',         'lat' => 25.2048,  'lng' => 55.2708],
    ['name' => 'São Paulo',   'region' => 'São Paulo',      'country' => 'Brazil',      'lat' => -23.5505, 'lng' => -46.6333],
    ['name' => 'Buenos Aires', 'region' => 'Buenos Aires',  'country' => 'Argentina',   'lat' => -34.6037, 'lng' => -58.3816],
];
