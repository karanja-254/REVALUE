<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => Organization::TYPE_CHARITY,
            'name' => fake()->company().' Home',
            'contact_person' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '07'.fake()->numerify('########'),
            'location' => fake()->randomElement(['Kilimani, Nairobi', 'Westlands, Nairobi', 'Parklands, Nairobi']),
            'registration_details' => 'NGO-'.fake()->numerify('####'),
            'website' => null,
            'supporting_document_path' => null,
            'verification_status' => Organization::STATUS_PENDING,
            'review_notes' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => Organization::STATUS_VERIFIED,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => Organization::STATUS_REJECTED,
            'review_notes' => 'Documents could not be confirmed.',
        ]);
    }

    public function recycler(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Organization::TYPE_RECYCLER,
        ]);
    }
}
