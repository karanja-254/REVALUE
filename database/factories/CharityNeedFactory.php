<?php

namespace Database\Factories;

use App\Models\CharityNeed;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CharityNeed>
 */
class CharityNeedFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'category' => fake()->randomElement(['mattresses', 'furniture', 'electronics', 'household']),
            'quantity' => fake()->numberBetween(1, 20),
            'description' => fake()->sentence(),
            'status' => CharityNeed::STATUS_OPEN,
        ];
    }
}
