<?php

namespace Tests\Feature\Logistics;

use App\Models\LogisticsRoute;
use App\Models\Order;
use App\Models\RouteStop;
use App\Services\Logistics\CollectionSchedule;
use App\Services\Logistics\RouteBatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteBatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_next_collection_date_is_a_wednesday_or_saturday(): void
    {
        $date = app(CollectionSchedule::class)->nextCollectionDate();

        $this->assertContains($date->dayOfWeek, LogisticsRoute::COLLECTION_DAYS);
    }

    public function test_building_a_route_batches_paid_orders_into_pickups_and_schedules_them(): void
    {
        Order::factory()->count(3)->paid()->create();

        $result = app(RouteBatchingService::class)->buildNextRoute();

        $this->assertSame(3, $result['pickups_added']);
        $this->assertSame(0, $result['deliveries_added']);
        $this->assertCount(3, $result['route']->stops);
        $this->assertSame(3, RouteStop::where('type', RouteStop::TYPE_PICKUP)->count());

        // Paid orders are moved to "scheduled" once they are on a run.
        $this->assertSame(3, Order::where('order_status', Order::STATUS_SCHEDULED)->count());
    }

    public function test_building_is_idempotent_and_does_not_duplicate_stops(): void
    {
        Order::factory()->count(2)->paid()->create();

        $service = app(RouteBatchingService::class);
        $service->buildNextRoute();
        $second = $service->buildNextRoute();

        $this->assertSame(0, $second['pickups_added']);
        $this->assertSame(2, RouteStop::where('type', RouteStop::TYPE_PICKUP)->count());
    }

    public function test_picked_up_orders_receive_delivery_stops(): void
    {
        Order::factory()->paid()->create(['order_status' => Order::STATUS_PICKED_UP]);

        $result = app(RouteBatchingService::class)->buildNextRoute();

        $this->assertSame(0, $result['pickups_added']);
        $this->assertSame(1, $result['deliveries_added']);
        $this->assertSame(1, RouteStop::where('type', RouteStop::TYPE_DELIVERY)->count());
    }

    public function test_route_for_a_date_is_reused_not_recreated(): void
    {
        $service = app(RouteBatchingService::class);
        $date = app(CollectionSchedule::class)->nextCollectionDate();

        $a = $service->routeForDate($date);
        $b = $service->routeForDate($date);

        $this->assertTrue($a->is($b));
        $this->assertSame(1, LogisticsRoute::count());
    }
}
