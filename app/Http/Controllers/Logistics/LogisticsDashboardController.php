<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsRoute;
use App\Models\Order;
use App\Models\RouteStop;
use App\Services\Logistics\CollectionSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogisticsDashboardController extends Controller
{
    public function __construct(private readonly CollectionSchedule $schedule) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $routesQuery = LogisticsRoute::query()
            ->with(['driver', 'stops'])
            ->withCount('stops')
            ->orderBy('collection_date');

        // A logistics driver only sees their own runs; admins see everything.
        if ($user->isLogistics() && ! $user->isAdmin()) {
            $routesQuery->where('driver_id', $user->id);
        }

        $upcomingRoutes = (clone $routesQuery)
            ->whereIn('status', [LogisticsRoute::STATUS_PLANNED, LogisticsRoute::STATUS_IN_PROGRESS])
            ->get();

        $recentRoutes = (clone $routesQuery)
            ->whereIn('status', [LogisticsRoute::STATUS_COMPLETED, LogisticsRoute::STATUS_CANCELLED])
            ->reorder('collection_date', 'desc')
            ->limit(5)
            ->get();

        $stats = [
            'awaiting_pickup' => Order::where('payment_status', Order::PAYMENT_PAID)
                ->whereIn('order_status', [Order::STATUS_PAID, Order::STATUS_SCHEDULED])
                ->whereDoesntHave('routeStops', fn ($q) => $q->where('type', RouteStop::TYPE_PICKUP))
                ->count(),
            'awaiting_delivery' => Order::whereIn('order_status', [Order::STATUS_PICKED_UP, Order::STATUS_OUT_FOR_DELIVERY])
                ->whereDoesntHave('routeStops', fn ($q) => $q->where('type', RouteStop::TYPE_DELIVERY))
                ->count(),
            'completed_today' => Order::where('order_status', Order::STATUS_COMPLETED)
                ->whereDate('delivery_verified_at', today())
                ->count(),
        ];

        return view('logistics.dashboard', [
            'upcomingRoutes' => $upcomingRoutes,
            'recentRoutes' => $recentRoutes,
            'stats' => $stats,
            'nextCollectionDate' => $this->schedule->nextCollectionDate(),
            'upcomingCollectionDates' => $this->schedule->upcomingCollectionDates(),
        ]);
    }
}
