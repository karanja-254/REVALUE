<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsRoute;
use App\Services\Logistics\RouteBatchingService;
use App\Support\Logistics\MapData;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RouteController extends Controller
{
    public function __construct(private readonly RouteBatchingService $batching) {}

    public function show(Request $request, LogisticsRoute $route): View
    {
        $this->authorizeRoute($request, $route);

        $route->load([
            'driver',
            'stops.order.listing.user',
            'stops.order.buyer',
        ]);

        return view('logistics.routes.show', [
            'route' => $route,
            'mapData' => MapData::forRoute($route),
            'mapsKey' => config('services.google_maps.key'),
        ]);
    }

    /**
     * Batch all outstanding pickups/deliveries onto a collection-day route.
     * Admins only (drivers do not create runs).
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'collection_date' => ['nullable', 'date'],
            'driver_id' => ['nullable', 'exists:users,id'],
        ]);

        $date = isset($validated['collection_date'])
            ? CarbonImmutable::parse($validated['collection_date'])
            : null;

        $result = $date
            ? $this->batching->buildRouteForDate($date, $validated['driver_id'] ?? null)
            : $this->batching->buildNextRoute($validated['driver_id'] ?? null);

        return redirect()
            ->route('logistics.routes.show', $result['route'])
            ->with('status', sprintf(
                'Route built: %d pickup(s) and %d delivery(ies) added.',
                $result['pickups_added'],
                $result['deliveries_added'],
            ));
    }

    public function start(Request $request, LogisticsRoute $route): RedirectResponse
    {
        $this->authorizeRoute($request, $route);

        $route->update([
            'status' => LogisticsRoute::STATUS_IN_PROGRESS,
            'started_at' => $route->started_at ?? now(),
        ]);

        return back()->with('status', 'Route started. Drive safe!');
    }

    public function complete(Request $request, LogisticsRoute $route): RedirectResponse
    {
        $this->authorizeRoute($request, $route);

        $route->update([
            'status' => LogisticsRoute::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return back()->with('status', 'Route marked complete.');
    }

    /**
     * A driver may only act on their own route; admins may act on any.
     */
    private function authorizeRoute(Request $request, LogisticsRoute $route): void
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        abort_unless($user->isLogistics() && $route->driver_id === $user->id, 403);
    }
}
