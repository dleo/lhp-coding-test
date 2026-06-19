export interface EventPayload {
    name: string;
    description: string;
    category: string;
    organizer: string;
    venue: {
        name: string;
        capacity: number;
    };
    location: {
        lat: number;
        lng: number;
    };
    schedule: {
        starts_at: number; // unix UTC seconds
        ends_at: number; // unix UTC seconds
    };
    pricing: {
        currency: string;
        min_price: number;
    };
    tags: string[];
}

export interface EventRow {
    id: string;
    user_id: string;
    type: string; // type = category
    status: 'draft' | 'published' | 'cancelled' | 'sold_out';
    created_time: number; // unix UTC seconds — event start
    latitude: number;
    longitude: number;
    payload: EventPayload;
    location_name: string; // appended accessor, e.g. "New York, USA"
    images: string[]; // appended accessor, 2–3 local URLs like /images/events/pool/07.webp
    user?: { id: string; name: string };
    created_at: string;
    updated_at: string;
}

export interface CityOption {
    name: string;
    region: string;
    country: string;
    lat: number;
    lng: number;
}

export type EventStatus = 'draft' | 'published' | 'cancelled' | 'sold_out';

export interface EventFilters {
    from: string;
    to: string;
    city: string;
    status: string;
}
