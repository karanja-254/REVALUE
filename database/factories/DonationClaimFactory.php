<?php

namespace Database\Factories;

use App\Models\DonationClaim;
use App\Models\Listing;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonationClaim>
 */
class DonationClaimFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory()->donation()->available(),
            'organization_id' => Organization::factory()->verified(),
            'claimed_by_user_id' => User::factory(),
            'status' => DonationClaim::STATUS_CLAIMED,
            'notes' => null,
        ];
    }
}
