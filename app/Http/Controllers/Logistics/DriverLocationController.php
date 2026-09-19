<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\UpdateDriverLocationRequest;
use App\Models\DriverLocation;
use App\Models\LogisticsRoute;
use App\Support\Logistics\MapData;
use Illuminate\Http\JsonResponse;

class DriverLocationController extends Controller
{
    /**
     * The driver's browser posts its GPS position here on an interval while a
     * route is in progress. One row per driver, updated in place.
     */
    public function store(UpdateDriverLocationRequest $request): JsonResponse
    {
        $user = $request->user();

        $location = DriverLocation::updateOrCreate(
            ['user_id' => $user->id],
            [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'heading' => $request->input('heading'),
                'accuracy' => $request->input('accuracy'),
                'speed' => $request->input('speed'),
                'recorded_at' => now(),
            ]
        );

        return response()->json([
            'ok' => true,
            'recorded_at' => $location->recorded_at?->toIso8601String(),
        ]);
    }

    /**
     * Polled by the route map to refresh marker positions without a reload.
     */
    public function routeLocations(LogisticsRoute $route): JsonResponse
    {
        $route->loadMissing(['stops.order.listing.user', 'stops.order.buyer', 'driver.driverLocation']);

        return response()->json(MapData::forRoute($route));
    }
}
