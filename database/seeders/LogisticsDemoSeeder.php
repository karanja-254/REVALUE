<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\LogisticsRoute;
use App\Models\Order;
use App\Models\RouteStop;
use App\Models\User;
use App\Services\Logistics\RouteBatchingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a realistic, end-to-end Maps/Logistics demo (Person 4).
 *
 * Creates a driver, sellers/buyers with Nairobi pickup & delivery locations,
 * paid + picked-up orders, and batches them onto the next collection-day route
 * so the logistics dashboard, route map, PIN flows and buyer tracking all have
 * live data to show. Development only.
 */
class LogisticsDemoSeeder extends Seeder
{
    /**
     * Nairobi neighbourhoods with rough coordinates.
     *
     * @var list<array{name: string, lat: float, lng: float}>
     */
    private array $places = [
        ['name' => 'Westlands, Nairobi', 'lat' => -1.2649, 'lng' => 36.8029],
        ['name' => 'Kilimani, Nairobi', 'lat' => -1.2907, 'lng' => 36.7860],
        ['name' => 'Karen, Nairobi', 'lat' => -1.3197, 'lng' => 36.7076],
        ['name' => 'Lavington, Nairobi', 'lat' => -1.2790, 'lng' => 36.7660],
        ['name' => 'Kasarani, Nairobi', 'lat' => -1.2210, 'lng' => 36.8990],
        ['name' => 'South B, Nairobi', 'lat' => -1.3080, 'lng' => 36.8370],
        ['name' => 'Embakasi, Nairobi', 'lat' => -1.3180, 'lng' => 36.8940],
        ['name' => 'Parklands, Nairobi', 'lat' => -1.2620, 'lng' => 36.8180],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('LogisticsDemoSeeder is development-only; skipping.');

            return;
        }

        if (User::where('email', 'driver@revalue.test')->exists()) {
            $this->command?->warn('Logistics demo already seeded; skipping.');

            return;
        }

        $driver = User::updateOrCreate(
            ['email' => 'driver@revalue.test'],
            [
                'name' => 'Dan the Driver',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => User::ROLE_LOGISTICS,
            ]
        );

        // Give the driver a current position (Nairobi CBD).
        $driver->driverLocation()->updateOrCreate([], [
            'latitude' => -1.286389,
            'longitude' => 36.817223,
            'heading' => 90,
            'accuracy' => 12,
            'speed' => 8,
            'recorded_at' => now(),
        ]);

        // 3 paid orders that still need a pickup.
        for ($i = 0; $i < 3; $i++) {
            $this->makeOrder(Order::STATUS_PAID);
        }

        // 2 orders already collected that still need delivery.
        for ($i = 0; $i < 2; $i++) {
            $this->makeOrder(Order::STATUS_PICKED_UP);
        }

        // Batch everything onto the next collection-day route, assigned to the driver.
        $batching = app(RouteBatchingService::class);
        $result = $batching->buildNextRoute($driver->id);
        $route = $result['route'];
        $route->update(['status' => LogisticsRoute::STATUS_IN_PROGRESS, 'started_at' => now()]);

        // Show some in-progress variety on the map.
        $route->pickups()->first()?->update(['status' => RouteStop::STATUS_EN_ROUTE]);

        $this->command?->info('Logistics demo seeded:');
        $this->command?->info('  Driver login: driver@revalue.test / password');
        $this->command?->info("  Route #{$route->id} with {$route->stops()->count()} stops on {$route->collection_date->toDateString()}");
        $buyer = Order::query()->latest('id')->first()?->buyer;
        if ($buyer) {
            $this->command?->info("  Buyer login: {$buyer->email} / password (view tracking)");
        }
    }

    /**
     * Create a seller, buyer, listing (with pickup location) and an order
     * (with delivery location + PINs) in the given order status.
     */
    private function makeOrder(string $orderStatus): Order
    {
        $pickupPlace = $this->places[array_rand($this->places)];
        $deliveryPlace = $this->places[array_rand($this->places)];

        $seller = User::factory()->create([
            'name' => fake()->name(),
            'role' => User::ROLE_USER,
            'password' => Hash::make('password'),
        ]);

        $buyer = User::factory()->create([
            'name' => fake()->name(),
            'role' => User::ROLE_USER,
            'password' => Hash::make('password'),
        ]);

        $listing = Listing::factory()->available()->create([
            'user_id' => $seller->id,
            'title' => fake()->randomElement([
                'Samsung 43" Smart TV', 'Leather 3-seater sofa', 'Double bed mattress',
                'Office desk', 'Microwave oven', 'Dining table set',
            ]),
            'pickup_address' => $pickupPlace['name'],
            'pickup_latitude' => $pickupPlace['lat'],
            'pickup_longitude' => $pickupPlace['lng'],
            'pickup_notes' => 'Call on arrival at the gate.',
        ]);

        $isPickedUp = $orderStatus === Order::STATUS_PICKED_UP;

        return Order::factory()->paid()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'order_status' => $orderStatus,
            'pickup_verified_at' => $isPickedUp ? now()->subHours(2) : null,
            'delivery_address' => $deliveryPlace['name'],
            'delivery_latitude' => $deliveryPlace['lat'],
            'delivery_longitude' => $deliveryPlace['lng'],
            'delivery_notes' => 'Leave with the watchman if not in.',
        ]);
    }
}
