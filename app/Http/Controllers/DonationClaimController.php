<?php

namespace App\Http\Controllers;

use App\Models\DonationClaim;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DonationClaimController extends Controller
{
    public function store(Request $request, Listing $listing): RedirectResponse
    {
        $organization = $request->user()->organization;

        abort_unless(
            $organization?->isVerified() && $organization->isCharity(),
            403,
            'Only verified charities can claim donations.'
        );

        abort_unless(
            $listing->isDonate() && $listing->isAvailable(),
            422,
            'This donation is no longer available.'
        );

        $listing->donationClaim()->create([
            'organization_id' => $organization->id,
            'claimed_by_user_id' => $request->user()->id,
            'status' => DonationClaim::STATUS_CLAIMED,
        ]);

        $listing->update([
            'status' => Listing::STATUS_DONATED,
        ]);

        return redirect()
            ->route('listings.show', $listing)
            ->with('status', 'Donation claimed. Arrange collection with the donor — they should not pay transport.');
    }
}
