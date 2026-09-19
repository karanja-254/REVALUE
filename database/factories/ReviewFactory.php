<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->completed(),
            'reviewer_id' => User::factory(),
            'accurate_description' => 5,
            'smooth_delivery' => 4,
            'professional_handling' => 5,
            'punctual_pickup' => 4,
            'comment' => 'Smooth ReValue handover. No bargaining, item matched the listing.',
        ];
    }
}
