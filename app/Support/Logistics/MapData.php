<?php

namespace App\Support\Logistics;

use App\Models\DriverLocation;
use App\Models\LogisticsRoute;
use App\Models\Order;
use App\Models\RouteStop;

/**
 * Builds the JSON payloads consumed by the front-end Google Maps component.
 *
 * Everything is plain arrays so it can be dropped straight into a data-*
 * attribute / Alpine component and rendered on mobile browsers.
 */
class MapData
{
    /**
     * Map data for a full logistics route (all stops + driver position).
     *
     * @return array<string, mixed>
     */
    public static function forRoute(LogisticsRoute $route): array
    {
        $route->loadMissing([
            'stops.order.listing.user',
            'stops.order.buyer',
            'driver.driverLocation',
        ]);

        $stops = $route->stops
            ->map(fn (RouteStop $stop) => self::stopPoint($stop))
            ->filter(fn ($point) => $point !== null)
            ->values()
            ->all();

        return [
            'stops' => $stops,
            'driver' => self::driverPoint($route->driver?->driverLocation),
            'center' => self::center($stops),
        ];
    }

    /**
     * Map data for a single order's tracking view (pickup, delivery, driver).
     *
     * @return array<string, mixed>
     */
    public static function forOrder(Order $order): array
    {
        $order->loadMissing([
            'listing.user',
            'buyer',
            'pickupStop.route.driver.driverLocation',
            'deliveryStop.route.driver.driverLocation',
        ]);

        $points = [];

        if ($order->listing?->hasPickupLocation()) {
            $points[] = [
                'type' => RouteStop::TYPE_PICKUP,
                'lat' => (float) $order->listing->pickup_latitude,
                'lng' => (float) $order->listing->pickup_longitude,
                'label' => 'Pickup',
                'address' => $order->listing->pickup_address,
                'status' => $order->pickupStop?->status,
            ];
        }

        if ($order->hasDeliveryLocation()) {
            $points[] = [
                'type' => RouteStop::TYPE_DELIVERY,
                'lat' => (float) $order->delivery_latitude,
                'lng' => (float) $order->delivery_longitude,
                'label' => 'Delivery',
                'address' => $order->delivery_address,
                'status' => $order->deliveryStop?->status,
            ];
        }

        // Prefer the driver on whichever leg is currently active.
        $activeRoute = $order->deliveryStop?->route?->isActive()
            ? $order->deliveryStop->route
            : $order->pickupStop?->route;

        return [
            'stops' => $points,
            'driver' => self::driverPoint($activeRoute?->driver?->driverLocation),
            'center' => self::center($points),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function stopPoint(RouteStop $stop): ?array
    {
        if (! $stop->hasCoordinates()) {
            return null;
        }

        return [
            'id' => $stop->id,
            'type' => $stop->type,
            'sequence' => $stop->sequence,
            'lat' => $stop->latitude,
            'lng' => $stop->longitude,
            'label' => ucfirst($stop->type).' #'.$stop->sequence,
            'address' => $stop->address,
            'contact' => $stop->contact_name,
            'status' => $stop->status,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function driverPoint(?DriverLocation $location): ?array
    {
        if ($location === null) {
            return null;
        }

        return [
            'lat' => (float) $location->latitude,
            'lng' => (float) $location->longitude,
            'heading' => $location->heading !== null ? (float) $location->heading : null,
            'recorded_at' => $location->recorded_at?->toIso8601String(),
            'stale' => $location->isStale(),
        ];
    }

    /**
     * Centre the map on the average of all known points, falling back to the
     * configured default (Nairobi).
     *
     * @param  array<int, array<string, mixed>>  $points
     * @return array{lat: float, lng: float}
     */
    private static function center(array $points): array
    {
        if ($points === []) {
            /** @var array{lat: float, lng: float} $default */
            $default = config('services.google_maps.default_center');

            return $default;
        }

        $lat = array_sum(array_column($points, 'lat')) / count($points);
        $lng = array_sum(array_column($points, 'lng')) / count($points);

        return ['lat' => $lat, 'lng' => $lng];
    }
}
