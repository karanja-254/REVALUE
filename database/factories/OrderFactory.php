<?php

namespace Database\Factories;

use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $itemPrice = fake()->numberBetween(500, 80_000);
        $deliveryFee = 600;
        $serviceFee = 300;

        return [
            'listing_id' => Listing::factory(),
            'buyer_id' => User::factory(),
            'item_price' => $itemPrice,
            'delivery_fee' => $deliveryFee,
            'service_fee' => $serviceFee,
            'total_amount' => $itemPrice + $deliveryFee + $serviceFee,
            'payment_reference' => null,
            'payment_status' => Order::PAYMENT_PENDING,
            'order_status' => Order::STATUS_PENDING_PAYMENT,
            'pickup_pin' => null,
            'delivery_pin' => null,
            'pickup_verified_at' => null,
            'delivery_verified_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_reference' => 'DEV-'.fake()->unique()->bothify('??######'),
            'payment_status' => Order::PAYMENT_PAID,
            'order_status' => Order::STATUS_PAID,
            'pickup_pin' => (string) fake()->numberBetween(1000, 9999),
            'delivery_pin' => (string) fake()->numberBetween(1000, 9999),
        ]);
    }
}
