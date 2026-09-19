<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCharityNeedRequest;
use App\Models\CharityNeed;
use App\Support\ListingOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CharityNeedController extends Controller
{
    public function create(Request $request): View
    {
        $organization = $request->user()->organization;

        abort_unless(
            $organization?->isVerified() && $organization->isCharity(),
            403
        );

        return view('charity-needs.create', [
            'organization' => $organization,
            'categories' => ListingOptions::categoryLabels(),
        ]);
    }

    public function store(StoreCharityNeedRequest $request): RedirectResponse
    {
        CharityNeed::create([
            ...$request->validated(),
            'organization_id' => $request->user()->organization->id,
            'status' => CharityNeed::STATUS_OPEN,
        ]);

        return redirect()
            ->route('organizations.show', $request->user()->organization)
            ->with('status', 'Need published. Donors can now match items to your request.');
    }
}
