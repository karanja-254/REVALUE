<?php

namespace Tests\Feature;

use App\Models\LogisticsRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogisticsMapTest extends TestCase
{
    use RefreshDatabase;

    private function routeForDriver(User $driver): LogisticsRoute
    {
        return LogisticsRoute::factory()->create([
            'driver_id' => $driver->id,
            'collection_date' => now()->addDay()->toDateString(),
        ]);
    }

    public function test_route_page_renders_the_live_map_canvas_when_a_maps_key_is_set(): void
    {
        config(['services.google_maps.key' => 'test-maps-key']);

        $driver = User::factory()->logistics()->create();
        $route = $this->routeForDriver($driver);

        $this->actingAs($driver)
            ->get(route('logistics.routes.show', $route))
            ->assertOk()
            ->assertSee('data-logistics-map', false)
            ->assertSee('data-map-canvas', false)
            ->assertSee('data-maps-key="test-maps-key"', false)
            ->assertDontSee('Live map disabled');
    }

    public function test_route_page_falls_back_to_a_location_list_without_a_maps_key(): void
    {
        config(['services.google_maps.key' => null]);

        $driver = User::factory()->logistics()->create();
        $route = $this->routeForDriver($driver);

        $this->actingAs($driver)
            ->get(route('logistics.routes.show', $route))
            ->assertOk()
            ->assertSee('Live map disabled');
    }
}
