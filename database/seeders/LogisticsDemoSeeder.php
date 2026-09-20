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

        if (LogisticsRoute::query()->exists()) {
            $this->command?->warn('Logistics demo already seeded; skipping.');

            return;
        }

        // Same logistics account listed in the demo credentials, so the route
        // he is assigned is the route he can actually open.
        $driver = User::updateOrCreate(
            ['email' => 'techtony@revalue.test'],
            [
                'name' => 'TechTony',
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

        // Demo seller — one order WITHOUT pickup location so they can try the picker.
        $demoSeller = User::updateOrCreate(
            ['email' => 'seller-demo@revalue.test'],
            [
                'name' => 'Demo Seller',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => User::ROLE_USER,
            ]
        );

        $this->makeOrder(Order::STATUS_PAID, seller: $demoSeller, withPickupLocation: false);

        // Demo buyer — one picked-up order WITHOUT delivery location.
        $demoBuyer = User::updateOrCreate(
            ['email' => 'buyer-demo@revalue.test'],
            [
                'name' => 'Demo Buyer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => User::ROLE_USER,
            ]
        );

        $this->makeOrder(Order::STATUS_PICKED_UP, buyer: $demoBuyer, withDeliveryLocation: false);

        // 2 more paid orders with full locations (for the route map).
        for ($i = 0; $i < 2; $i++) {
            $this->makeOrder(Order::STATUS_PAID);
        }

        // 1 more picked-up order with full locations.
        $this->makeOrder(Order::STATUS_PICKED_UP);

        // Batch everything onto the next collection-day route, assigned to the driver.
        $batching = app(RouteBatchingService::class);
        $result = $batching->buildNextRoute($driver->id);
        $route = $result['route'];
        $route->update(['status' => LogisticsRoute::STATUS_IN_PROGRESS, 'started_at' => now()]);

        // Show some in-progress variety on the map.
        $route->pickups()->first()?->update(['status' => RouteStop::STATUS_EN_ROUTE]);

        $this->command?->info('Logistics demo seeded:');
        $this->command?->info('  Driver login: techtony@revalue.test / password');
        $this->command?->info('  Seller (share pickup): seller-demo@revalue.test / password');
        $this->command?->info('  Buyer (share delivery): buyer-demo@revalue.test / password');
        $this->command?->info("  Route #{$route->id} with {$route->stops()->count()} stops on {$route->collection_date->toDateString()}");
    }

    /**
     * Create a seller, buyer, listing and order in the given order status.
     */
    private function makeOrder(
        string $orderStatus,
        ?User $seller = null,
        ?User $buyer = null,
        bool $withPickupLocation = true,
        bool $withDeliveryLocation = true,
    ): Order {
        $pickupPlace = $this->places[array_rand($this->places)];
        $deliveryPlace = $this->places[array_rand($this->places)];

        $seller ??= User::factory()->create([
            'name' => fake()->name(),
            'role' => User::ROLE_USER,
            'password' => Hash::make('password'),
        ]);

        $buyer ??= User::factory()->create([
            'name' => fake()->name(),
            'role' => User::ROLE_USER,
            'password' => Hash::make('password'),
        ]);

        $itemPrice = 10;
        $deliveryFee = (float) config('revalue.fees.delivery');
        $serviceFee = (float) config('revalue.fees.service');

        $listing = Listing::factory()->available()->create([
            'user_id' => $seller->id,
            'suggested_price' => $itemPrice,
            'final_price' => $itemPrice,
            'title' => fake()->randomElement([
                'Samsung 43" Smart TV', 'Leather 3-seater sofa', 'Double bed mattress',
                'Office desk', 'Microwave oven', 'Dining table set',
            ]),
            'pickup_address' => $withPickupLocation ? $pickupPlace['name'] : null,
            'pickup_latitude' => $withPickupLocation ? $pickupPlace['lat'] : null,
            'pickup_longitude' => $withPickupLocation ? $pickupPlace['lng'] : null,
            'pickup_notes' => $withPickupLocation ? 'Call on arrival at the gate.' : null,
        ]);

        $isPickedUp = $orderStatus === Order::STATUS_PICKED_UP;

        return Order::factory()->paid()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'order_status' => $orderStatus,
            'item_price' => $itemPrice,
            'delivery_fee' => $deliveryFee,
            'service_fee' => $serviceFee,
            'total_amount' => $itemPrice + $deliveryFee + $serviceFee,
            'pickup_verified_at' => $isPickedUp ? now()->subHours(2) : null,
            'delivery_address' => $withDeliveryLocation ? $deliveryPlace['name'] : null,
            'delivery_latitude' => $withDeliveryLocation ? $deliveryPlace['lat'] : null,
            'delivery_longitude' => $withDeliveryLocation ? $deliveryPlace['lng'] : null,
            'delivery_notes' => $withDeliveryLocation ? 'Leave with the watchman if not in.' : null,
        ]);
    }
}
