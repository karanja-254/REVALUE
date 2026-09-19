<?php

namespace App\Services\Logistics;

use App\Models\LogisticsRoute;
use App\Models\Order;
use App\Models\RouteStop;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Batches paid orders into collection-day routes and manages route stops.
 *
 * Pickups are needed for orders that have been paid but not yet collected.
 * Deliveries are needed for orders that have been picked up but not delivered.
 */
class RouteBatchingService
{
    public function __construct(private readonly CollectionSchedule $schedule) {}

    /**
     * Find (or create) the route for a given collection date.
     */
    public function routeForDate(CarbonInterface $date, ?int $driverId = null): LogisticsRoute
    {
        $date = $date->copy()->startOfDay();

        // Match on the date part only: the `date` cast persists the value as
        // "Y-m-d 00:00:00", so an exact string match on "Y-m-d" would miss.
        $route = LogisticsRoute::whereDate('collection_date', $date->toDateString())->first();

        if ($route === null) {
            $route = LogisticsRoute::create([
                'name' => $date->format('l, M j').' collection run',
                'collection_date' => $date->toDateString(),
                'driver_id' => $driverId,
                'status' => LogisticsRoute::STATUS_PLANNED,
            ]);
        }

        return $route;
    }

    /**
     * Build (or top up) the route for the next collection day by batching all
     * outstanding pickups and deliveries onto it.
     *
     * @return array{route: LogisticsRoute, pickups_added: int, deliveries_added: int}
     */
    public function buildNextRoute(?int $driverId = null): array
    {
        $date = $this->schedule->nextCollectionDate();

        return $this->buildRouteForDate($date, $driverId);
    }

    /**
     * @return array{route: LogisticsRoute, pickups_added: int, deliveries_added: int}
     */
    public function buildRouteForDate(CarbonInterface $date, ?int $driverId = null): array
    {
        return DB::transaction(function () use ($date, $driverId) {
            $route = $this->routeForDate($date, $driverId);

            $pickups = 0;
            $deliveries = 0;

            foreach ($this->ordersNeedingPickup() as $order) {
                if ($this->addStop($route, $order, RouteStop::TYPE_PICKUP)) {
                    $pickups++;
                }
            }

            foreach ($this->ordersNeedingDelivery() as $order) {
                if ($this->addStop($route, $order, RouteStop::TYPE_DELIVERY)) {
                    $deliveries++;
                }
            }

            $this->resequence($route);

            return [
                'route' => $route->fresh('stops'),
                'pickups_added' => $pickups,
                'deliveries_added' => $deliveries,
            ];
        });
    }

    /**
     * Attach a single order to a route as a pickup or delivery stop.
     *
     * Returns false when the order already has a stop of that type anywhere
     * (each order has at most one pickup and one delivery across all routes).
     */
    public function addStop(LogisticsRoute $route, Order $order, string $type): bool
    {
        $exists = RouteStop::where('order_id', $order->id)
            ->where('type', $type)
            ->exists();

        if ($exists) {
            return false;
        }

        $nextSequence = (int) $route->stops()->max('sequence') + 1;

        RouteStop::create([
            'route_id' => $route->id,
            'order_id' => $order->id,
            'type' => $type,
            'sequence' => $nextSequence,
            'status' => RouteStop::STATUS_PENDING,
        ]);

        // Marking a pickup as scheduled moves the order out of the "paid"
        // backlog and lets the buyer/seller see it is now on a run.
        if ($type === RouteStop::TYPE_PICKUP && $order->order_status === Order::STATUS_PAID) {
            $order->update(['order_status' => Order::STATUS_SCHEDULED]);
        }

        return true;
    }

    /**
     * Re-number stops sequentially, keeping all pickups before deliveries so
     * an item is always collected before it is delivered on the same run.
     */
    public function resequence(LogisticsRoute $route): void
    {
        $ordered = $route->stops()
            ->get()
            ->sortBy([
                fn (RouteStop $a, RouteStop $b) => ($a->type === RouteStop::TYPE_PICKUP ? 0 : 1)
                    <=> ($b->type === RouteStop::TYPE_PICKUP ? 0 : 1),
                fn (RouteStop $a, RouteStop $b) => $a->id <=> $b->id,
            ])
            ->values();

        foreach ($ordered as $index => $stop) {
            $stop->update(['sequence' => $index + 1]);
        }
    }

    /**
     * Orders that have been paid (or already scheduled) but not yet collected.
     */
    public function ordersNeedingPickup()
    {
        return Order::query()
            ->where('payment_status', Order::PAYMENT_PAID)
            ->whereIn('order_status', [Order::STATUS_PAID, Order::STATUS_SCHEDULED])
            ->whereDoesntHave('routeStops', fn ($q) => $q->where('type', RouteStop::TYPE_PICKUP))
            ->with('listing.user')
            ->get();
    }

    /**
     * Orders that have been picked up but do not yet have a delivery stop.
     */
    public function ordersNeedingDelivery()
    {
        return Order::query()
            ->whereIn('order_status', [Order::STATUS_PICKED_UP, Order::STATUS_OUT_FOR_DELIVERY])
            ->whereDoesntHave('routeStops', fn ($q) => $q->where('type', RouteStop::TYPE_DELIVERY))
            ->with('buyer')
            ->get();
    }
}
