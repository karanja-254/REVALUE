<?php

namespace Database\Factories;

use App\Models\Listing;
use App\Models\PriceOverride;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceOverrideFactory extends Factory
{
    protected $model = PriceOverride::class;

    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'admin_id' => User::factory(),
            'old_price' => 8000.00,
            'new_price' => 7500.00,
            'reason' => $this->faker->sentence(),
            'override_at' => now(),
        ];
    }
}
