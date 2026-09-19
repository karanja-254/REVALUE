<?php

namespace Database\Factories;

use App\Models\Listing;
use App\Models\ManualReview;
use Illuminate\Database\Eloquent\Factories\Factory;

class ManualReviewFactory extends Factory
{
    protected $model = ManualReview::class;

    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'status' => 'pending',
            'notes' => $this->faker->sentence(),
        ];
    }
}
