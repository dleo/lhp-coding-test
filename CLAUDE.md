# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A take-home coding test (see `CODING_TEST.md`) built on the Laravel Vue starter kit. The
deliverable is to build out two distinct "Event Visuals" browsing pages plus end-to-end image
support, human-readable addresses from lat/lng, date/location filtering, an attendee/RSVP flow,
and confirmation + reminder emails (3 days and 24 hours before an event). Most of that is **not
yet built** — the repo ships the scaffolding and a deliberately bare listing.

Stack: PHP 8.3+ / Laravel 13, Inertia v3, Vue 3 (`<script setup>` + TS), Tailwind v4, Vite,
Pest 4. Auth is Laravel Fortify + passkeys (`@laravel/passkeys`). Default DB is SQLite.

## Commands

```bash
composer dev          # full dev stack: artisan serve + queue:listen + pail (logs) + vite, concurrently
npm run build         # build frontend assets

# Tests (Pest)
php artisan test                          # run the suite
php artisan test --filter='filters the data endpoint'   # single test by name
vendor/bin/pest --filter='...'            # equivalent

# Quality gates
composer lint           # Pint (auto-fix)         | composer lint:check  (verify only)
composer types:check    # PHPStan/Larastan level 7
npm run lint            # ESLint (auto-fix)       | npm run lint:check
npm run format          # Prettier                | npm run format:check
npm run types:check     # vue-tsc

composer test           # config:clear + pint check + phpstan + artisan test
composer ci:check       # composer test PLUS the npm lint/format/types checks — full gate
```

CI runs `composer types:check` + `php artisan test` (tests.yml) and Pint + Prettier + ESLint
(lint.yml). Match both before declaring done.

## Seeding the dataset

The test is explicitly built against a **large** dataset — `EventSeeder` defaults to
**1,250,000 events** and 3,000 users. Control the row count with the `SEED_ROWS` env var; use a
small value locally or seeding/migrations will be slow:

```bash
SEED_ROWS=2000 php artisan migrate:fresh --seed
```

`EventFactory` is the small/test-data path (used by Pest); `EventSeeder::insertEvents()` is the
bulk path (template-substituted payloads, SQLite write pragmas). Performance against the full
dataset is a first-class concern — the listing endpoint already reports query `ms` and response
`bytes`.

## Data model — read this before touching events

The `events` table is intentionally awkward; the "real" event detail lives in a JSON column:

- **Top-level columns**: `id` (UUID), `user_id`, `type` (= category), `status`
  (`draft|published|cancelled|sold_out`), `created_time`, `latitude`, `longitude`, timestamps.
- **`created_time`** is a **unix timestamp of the event's start**, not a Laravel created-at. It
  is what the listing orders by. Don't treat it as a row-creation time.
- **`payload`** (cast to `array`) holds: `name`, `description`, `category`, `organizer`, `venue`
  (`name`, `capacity`), `location` (`lat`, `lng`), `schedule` (`starts_at`, `ends_at` — unix),
  `pricing` (`currency`, `min_price`), `tags`.
- Events carry **only lat/lng** — there is no address column yet (the test asks you to derive a
  human-readable location). There are **no images** yet either (also to be added end-to-end,
  served locally — no hotlinked URLs).

## Request / render flow

- Routes (`routes/web.php`): `/` → `/events`. `events.index` renders the Inertia shell;
  **`events.data`** is a JSON endpoint for infinite-scroll pagination; `events.show` is the
  detail page. `events-visual-1` / `events-visual-2` map straight to `Events/VisualOne` and
  `Events/VisualTwo` via `Route::inertia` (the two pages to build).
- `EventController@index` returns only filter scaffolding; rows are fetched client-side by
  `Events/Index.vue` via `fetch('/events/data')` with an `IntersectionObserver`. The controller
  paginates 50/page with `->with('user')` and reports `stats.ms` / `stats.bytes`.
- Inertia pages live in `resources/js/pages/`. Layout is auto-assigned in `resources/js/app.ts`
  (`auth/*` → AuthLayout, `settings/*` → AppLayout+SettingsLayout, `Welcome` → none, else
  AppLayout). UI primitives are shadcn-vue style under `@/components/ui` (reka-ui +
  class-variance-authority). Typed route helpers come from Laravel Wayfinder.

### Known scaffolding gaps (the test surface, not bugs to silently "fix")

`Events/Index.vue` wires a `from` date input and a Filter button, but the button calls
`aplyFilters` (typo — the real fn is `applyFilters`) and `EventController` only filters by
`status`, ignoring `from`. These are starting points for the filtering requirement, not
accidental regressions — implement the intended behavior rather than just patching the typo.

## Conventions

- PHP: `declare(strict_types=1)`, PSR-12, Pint `laravel` preset, PHPStan level 7. Keep
  controllers thin; the existing `Settings` controllers use Form Requests and `Actions/` — follow
  that for new write paths (attendee registration, etc.).
- Vue: Composition API only, TypeScript, no `any`; Prettier with the Tailwind class-sorting
  plugin.
- Attendee + email work will need a model/migration, a Mailable or Notification, and scheduled
  reminders — the queue (`QUEUE_CONNECTION=database`) and `php artisan queue:listen` are already
  in `composer dev`; mail defaults to the `log` driver locally.
