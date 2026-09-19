<?php

namespace Tests\Feature\Logistics;

use App\Models\LogisticsRoute;
use App\Models\Order;
use App\Models\RouteStop;
use App\Models\User;
use App\Services\Logistics\CollectionSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogisticsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_users_cannot_open_the_logistics_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('logistics.dashboard'))
            ->assertForbidden();
    }

    public function test_logistics_and_admin_users_can_open_the_dashboard(): void
    {
        $this->actingAs(User::factory()->logistics()->create())
            ->get(route('logistics.dashboard'))
            ->assertOk();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('logistics.dashboard'))
            ->assertOk();
    }

    public function test_only_admins_can_batch_a_route(): void
    {
        Order::factory()->paid()->create();

        $this->actingAs(User::factory()->logistics()->create())
            ->post(route('logistics.routes.store'))
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('logistics.routes.store'))
            ->assertRedirect();

        $this->assertSame(1, LogisticsRoute::count());
    }

    public function test_a_non_wednesday_saturday_collection_date_is_rejected(): void
    {
        // Day after a valid collection day is always a non-collection day.
        $badDate = app(CollectionSchedule::class)->nextCollectionDate()->addDay();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('logistics.routes.store'), ['collection_date' => $badDate->toDateString()])
            ->assertSessionHasErrors('collection_date');

        $this->assertSame(0, LogisticsRoute::count());
    }

    public function test_a_wednesday_or_saturday_collection_date_is_accepted(): void
    {
        $goodDate = app(CollectionSchedule::class)->nextCollectionDate();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('logistics.routes.store'), ['collection_date' => $goodDate->toDateString()])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(1, LogisticsRoute::count());
    }

    public function test_a_normal_user_cannot_be_assigned_as_route_driver(): void
    {
        $customer = User::factory()->create(); // role = user

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('logistics.routes.store'), ['driver_id' => $customer->id])
            ->assertSessionHasErrors('driver_id');

        $this->assertSame(0, LogisticsRoute::count());
    }

    public function test_a_logistics_user_can_be_assigned_as_route_driver(): void
    {
        $driver = User::factory()->logistics()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('logistics.routes.store'), ['driver_id' => $driver->id])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $route = LogisticsRoute::firstOrFail();
        $this->assertSame($driver->id, $route->driver_id);
    }

    public function test_a_driver_cannot_verify_a_stop_on_another_drivers_route(): void
    {
        $driverA = User::factory()->logistics()->create();
        $driverB = User::factory()->logistics()->create();

        $order = Order::factory()->paid()->create(['order_status' => Order::STATUS_SCHEDULED]);
        $route = LogisticsRoute::factory()->create(['driver_id' => $driverA->id]);
        $stop = RouteStop::factory()->pickup()->create([
            'route_id' => $route->id,
            'order_id' => $order->id,
        ]);

        $this->actingAs($driverB)
            ->post(route('logistics.stops.verify-pickup', $stop), [
                'item_matches' => '1',
                'pin' => $order->pickup_pin,
            ])
            ->assertForbidden();
    }

    public function test_assigned_driver_can_verify_pickup_over_http(): void
    {
        $driver = User::factory()->logistics()->create();
        $order = Order::factory()->paid()->create(['order_status' => Order::STATUS_SCHEDULED]);
        $route = LogisticsRoute::factory()->create(['driver_id' => $driver->id]);
        $stop = RouteStop::factory()->pickup()->create([
            'route_id' => $route->id,
            'order_id' => $order->id,
        ]);

        $this->actingAs($driver)
            ->from(route('logistics.routes.show', $route))
            ->post(route('logistics.stops.verify-pickup', $stop), [
                'item_matches' => '1',
                'pin' => $order->pickup_pin,
            ])
            ->assertRedirect(route('logistics.routes.show', $route));

        $this->assertSame(Order::STATUS_PICKED_UP, $order->fresh()->order_status);
    }

    public function test_driver_location_can_be_reported(): void
    {
        $driver = User::factory()->logistics()->create();

        $this->actingAs($driver)
            ->postJson(route('logistics.driver.location'), [
                'latitude' => -1.29,
                'longitude' => 36.82,
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('driver_locations', ['user_id' => $driver->id]);
    }
}
