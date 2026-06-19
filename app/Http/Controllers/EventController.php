<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\LocationResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    private const CITY_BOX_DEGREES = 0.6;

    public function index(Request $request): Response
    {
        return Inertia::render('Events/Index', $this->listingScaffold($request));
    }

    public function visualOne(Request $request): Response
    {
        return Inertia::render('Events/VisualOne', $this->listingScaffold($request));
    }

    public function visualTwo(Request $request): Response
    {
        return Inertia::render('Events/VisualTwo', $this->listingScaffold($request));
    }

    public function data(Request $request): JsonResponse
    {
        [$events, $stats] = $this->loadListing($request);

        return response()->json([
            'data' => $events->items(),
            'current_page' => $events->currentPage(),
            'last_page' => $events->lastPage(),
            'total' => $events->total(),
            'stats' => $stats,
        ]);
    }

    public function show(Event $event): Response
    {
        $event->load('user');

        return Inertia::render('Events/Show', [
            'event' => $event,
        ]);
    }

    public function calendar(Request $request): JsonResponse
    {
        $start = microtime(true);

        $monthParam = $request->input('month', '');
        $month = (is_string($monthParam) && preg_match('/^\d{4}-\d{2}$/', $monthParam))
            ? $monthParam
            : date('Y-m');

        $monthStart = (int) strtotime($month.'-01 00:00:00 UTC');
        $monthEnd = (int) strtotime('+1 month', $monthStart) - 1;

        $dayExpr = $this->dayExpression();

        /** @var array<string, int> $rows */
        $rows = $this->applyFilters(Event::query(), $request)
            ->where('created_time', '>=', $monthStart)
            ->where('created_time', '<=', $monthEnd)
            ->selectRaw("{$dayExpr} as day, count(*) as cnt")
            ->groupByRaw($dayExpr)
            ->pluck('cnt', 'day')
            ->all();

        /** @var array<string, int> $counts */
        $counts = array_map('intval', $rows);

        $payload = ['month' => $month, 'counts' => $counts];
        $encoded = (string) json_encode($payload);

        $stats = [
            'ms' => (int) round((microtime(true) - $start) * 1000),
            'bytes' => strlen($encoded),
        ];

        return response()->json(array_merge($payload, ['stats' => $stats]));
    }

    /**
     * @return array<string, mixed>
     */
    private function listingScaffold(Request $request): array
    {
        return [
            'filters' => [
                'status' => $request->status,
                'from' => $request->input('from', '2023-01-01'),
                'to' => $request->input('to', ''),
                'city' => $request->input('city', ''),
            ],
            'statuses' => ['draft', 'published', 'cancelled', 'sold_out'],
            'cities' => LocationResolver::cities(),
        ];
    }

    /**
     * @return array{0: LengthAwarePaginator, 1: array{ms: int, bytes: int}}
     */
    private function loadListing(Request $request): array
    {
        $start = microtime(true);

        $from = $request->input('from');
        $to = $request->input('to');

        $events = $this->applyFilters(Event::with('user'), $request)
            ->when(
                $from !== null && $from !== '',
                fn ($q) => $q->where('created_time', '>=', strtotime((string) $from))
            )
            ->when(
                $to !== null && $to !== '',
                fn ($q) => $q->where('created_time', '<=', strtotime((string) $to))
            )
            ->orderByDesc('created_time')
            ->paginate(50)
            ->withQueryString();

        $stats = [
            'ms' => (int) round((microtime(true) - $start) * 1000),
            'bytes' => strlen((string) json_encode($events->items())),
        ];

        return [$events, $stats];
    }

    /**
     * Return a driver-portable SQL expression that converts a unix-timestamp
     * column `created_time` to a YYYY-MM-DD date string.
     *
     * No user input is interpolated — the expression is a constant per driver.
     *
     * @return literal-string
     */
    private function dayExpression(): string
    {
        return match ((new Event)->getConnection()->getDriverName()) {
            'mysql', 'mariadb' => 'date(from_unixtime(created_time))',
            default => "date(created_time, 'unixepoch')",
        };
    }

    /**
     * Apply shared status + city-bbox filters (no date range — each caller owns that).
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    private function applyFilters(Builder $query, Request $request): Builder
    {
        $cityIndex = $request->input('city');

        return $query
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when(
                $cityIndex !== null && $cityIndex !== '',
                function ($q) use ($cityIndex) {
                    $cities = LocationResolver::cities();
                    $index = (int) $cityIndex;

                    if (! isset($cities[$index])) {
                        return $q;
                    }

                    $anchor = $cities[$index];
                    $box = self::CITY_BOX_DEGREES;

                    return $q->whereBetween('latitude', [$anchor['lat'] - $box, $anchor['lat'] + $box])
                        ->whereBetween('longitude', [$anchor['lng'] - $box, $anchor['lng'] + $box]);
                }
            );
    }
}
