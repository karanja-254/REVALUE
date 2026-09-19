<?php

namespace Database\Factories;

use App\Models\LogisticsRoute;
use App\Models\Order;
use App\Models\RouteStop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RouteStop>
 */
class RouteStopFactory extends Factory
{
    protected $model = RouteStop::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'route_id' => LogisticsRoute::factory(),
            'order_id' => Order::factory()->paid(),
            'type' => RouteStop::TYPE_PICKUP,
            'sequence' => 1,
            'status' => RouteStop::STATUS_PENDING,
        ];
    }

    public function pickup(): static
    {
        return $this->state(fn () => ['type' => RouteStop::TYPE_PICKUP]);
    }

    public function delivery(): static
    {
        return $this->state(fn () => ['type' => RouteStop::TYPE_DELIVERY]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => RouteStop::STATUS_COMPLETED,
            'arrived_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }
}
