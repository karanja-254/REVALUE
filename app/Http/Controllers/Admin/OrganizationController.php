<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(): View
    {
        return view('admin.organizations.index', [
            'pending' => Organization::query()->where('verification_status', Organization::STATUS_PENDING)->latest()->get(),
            'verified' => Organization::query()->where('verification_status', Organization::STATUS_VERIFIED)->latest()->get(),
            'rejected' => Organization::query()->where('verification_status', Organization::STATUS_REJECTED)->latest()->get(),
        ]);
    }

    public function verify(Request $request, Organization $organization): RedirectResponse
    {
        $organization->update([
            'verification_status' => Organization::STATUS_VERIFIED,
            'review_notes' => $request->string('review_notes')->toString() ?: 'Verified by ReValue admin.',
        ]);

        return back()->with('status', $organization->name.' is now a verified organization.');
    }

    public function reject(Request $request, Organization $organization): RedirectResponse
    {
        $request->validate([
            'review_notes' => ['required', 'string', 'max:500'],
        ]);

        $organization->update([
            'verification_status' => Organization::STATUS_REJECTED,
            'review_notes' => $request->string('review_notes')->toString(),
        ]);

        return back()->with('status', $organization->name.' was rejected.');
    }
}
