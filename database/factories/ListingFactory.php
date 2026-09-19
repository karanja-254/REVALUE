<?php

namespace Database\Factories;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $suggestedPrice = fake()->numberBetween(500, 80_000);

        return [
            'user_id' => User::factory(),
            'type' => Listing::TYPE_SELL,
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['furniture', 'electronics', 'appliances', 'household']),
            'condition' => fake()->randomElement(['new', 'good', 'fair', 'damaged']),
            'image_path' => null,
            'suggested_price' => $suggestedPrice,
            'final_price' => $suggestedPrice,
            'status' => Listing::STATUS_DRAFT,
        ];
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Listing::STATUS_AVAILABLE,
        ]);
    }

    public function donation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Listing::TYPE_DONATE,
            'suggested_price' => null,
            'final_price' => null,
        ]);
    }

    /**
     * Named `recycling` because `recycle` is reserved by the base factory.
     */
    public function recycling(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Listing::TYPE_RECYCLE,
            'suggested_price' => null,
            'final_price' => null,
        ]);
    }
}
