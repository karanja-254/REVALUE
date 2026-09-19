<?php

namespace Database\Factories;

use App\Models\DriverLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverLocation>
 */
class DriverLocationFactory extends Factory
{
    protected $model = DriverLocation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Scatter around Nairobi CBD.
        return [
            'user_id' => User::factory(),
            'latitude' => fake()->randomFloat(6, -1.35, -1.22),
            'longitude' => fake()->randomFloat(6, 36.75, 36.90),
            'heading' => fake()->randomFloat(1, 0, 359),
            'accuracy' => fake()->randomFloat(1, 5, 30),
            'speed' => fake()->randomFloat(1, 0, 15),
            'recorded_at' => now(),
        ];
    }
}
