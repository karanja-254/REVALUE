<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\SellerPayout;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerPayout>
 */
class SellerPayoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'seller_id' => User::factory(),
            'amount' => fake()->numberBetween(500, 80_000),
            'mpesa_number' => null,
            'mpesa_receipt' => null,
            'status' => SellerPayout::STATUS_PENDING,
            'paid_by' => null,
            'paid_at' => null,
        ];
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SellerPayout::STATUS_READY,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SellerPayout::STATUS_PAID,
            'mpesa_number' => '2547'.fake()->numerify('########'),
            'mpesa_receipt' => strtoupper(fake()->bothify('??######??')),
            'paid_by' => User::factory()->admin(),
            'paid_at' => now(),
        ]);
    }
}
