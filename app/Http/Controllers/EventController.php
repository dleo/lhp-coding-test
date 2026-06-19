<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\LocationResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    private const CITY_BOX_DEGREES = 0.6;

    public function index(Request $request): Response
    {
        return Inertia::render('Events/Index', [
            'filters' => [
                'status' => $request->status,
                'from' => $request->input('from', '2023-01-01'),
                'to' => $request->input('to', ''),
                'city' => $request->input('city', ''),
            ],
            'statuses' => ['draft', 'published', 'cancelled', 'sold_out'],
            'cities' => LocationResolver::cities(),
        ]);
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

    /**
     * @return array{0: LengthAwarePaginator, 1: array{ms: int, bytes: int}}
     */
    private function loadListing(Request $request): array
    {
        $start = microtime(true);

        $from = $request->input('from');
        $to = $request->input('to');
        $cityIndex = $request->input('city');

        $events = Event::with('user')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when(
                $from !== null && $from !== '',
                fn ($q) => $q->where('created_time', '>=', strtotime((string) $from))
            )
            ->when(
                $to !== null && $to !== '',
                fn ($q) => $q->where('created_time', '<=', strtotime((string) $to))
            )
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
}
