<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(): View
    {
        $charities = Organization::query()
            ->verified()
            ->charities()
            ->with(['needs' => fn ($query) => $query->where('status', 'open')])
            ->latest()
            ->get();

        return view('organizations.index', [
            'charities' => $charities,
        ]);
    }

    public function show(Organization $organization): View
    {
        abort_unless($organization->isVerified(), 404);

        $organization->load(['needs', 'donationClaims.listing']);

        return view('organizations.show', [
            'organization' => $organization,
        ]);
    }

    public function create(Request $request): View
    {
        return view('organizations.create', [
            'organization' => $request->user()->organization,
        ]);
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        if ($request->user()->organization) {
            return back()->withErrors([
                'name' => 'This account already has an organization application.',
            ]);
        }

        $documentPath = null;

        if ($request->hasFile('supporting_document')) {
            $documentPath = $request->file('supporting_document')->store('organization-documents', 'public');
        }

        Organization::create([
            ...$request->safe()->except('supporting_document'),
            'user_id' => $request->user()->id,
            'supporting_document_path' => $documentPath,
            'verification_status' => Organization::STATUS_PENDING,
        ]);

        return redirect()
            ->route('organizations.create')
            ->with('status', 'Application received. A ReValue admin will review it before you can claim donations.');
    }
}
