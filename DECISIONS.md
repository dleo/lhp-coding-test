# Engineering Decisions

## Data model

- Event detail lives in a JSON `payload` column (`name`, `description`, `venue`, `schedule`, `pricing`, `tags`, etc.). Top-level columns are `id` (UUID), `user_id`, `type`, `status`, `latitude`, `longitude`, and `created_time`.
- `created_time` is a **unix timestamp of the event's start**, not a row-creation time. All listings sort by this column; do not confuse it with `created_at`.

## Performance at scale

- Dataset target is ~1.25 M events. The listing endpoint paginates 50 rows per page with server-side filtering (status, date range, city). Infinite scroll is client-driven via `IntersectionObserver`.
- Each response includes `stats.ms` and `stats.bytes` as first-class signals so slow queries surface immediately.
- The month calendar view uses a dedicated `/events/calendar` counts endpoint that returns per-day aggregates, avoiding shipping raw rows to the client just to count them.

## Location

- Events carry only `latitude`/`longitude` — no address column exists.
- Human-readable names are derived server-side by `App\Support\LocationResolver`, which finds the nearest city by squared-distance within a bounding box. Results are cached to avoid repeated lookups.
- City filtering accepts a `?city=` query parameter matched against this resolved name.

## Images

- No external hotlinks. A deterministic local pool (`public/images/events/pool/`) is assigned per event via `App\Support\EventImageResolver`.
- Events expose an `images` accessor that returns 2–3 pool images, consistently keyed to the event ID so the same event always shows the same images.

## Dates and timezone

- All timestamps are unix UTC.
- The frontend formats display dates using `Intl.DateTimeFormat` in the **viewer's** local timezone — no server-side timezone conversion.
- Caveat: the calendar's day bucketing (`from_unixtime` in DB) and the day-panel range query (`strtotime` on the server) both operate in the server's timezone. At day edges near UTC offset boundaries these can disagree with what the viewer sees; acceptable for this scope.

## Two visual pages

- **VisualOne** — animated infinite-scroll card grid with per-card entrance animations.
- **VisualTwo** — month calendar with per-day event counts; clicking a day opens a panel that reuses the shared `EventCard` component.
- Both pages share `EventCard`, `EventImage`, `EventFilters`, and the `formatDate` helper to avoid duplication.

## UI: filter select sentinel

- reka-ui `SelectItem` rejects an empty-string value. All "show all" filter options use the sentinel string `'all'`, which is normalized back to an empty string in the emitted filter payload before it reaches the query string.

## Attendees and email

- RSVP writes a row to the `attendees` table (one per user + event, enforced by a unique index).
- A confirmation email is dispatched immediately on registration.
- Reminder emails (3-day and 24-hour) are sent by a scheduled command (`SendEventReminders`) that runs every 15 minutes, queried against `reminder_3day_sent_at` / `reminder_24hr_sent_at` columns to prevent double-sends.
- All mail is queued via the `database` queue driver (`QUEUE_CONNECTION=database`); locally it logs to `storage/logs/laravel.log`.
