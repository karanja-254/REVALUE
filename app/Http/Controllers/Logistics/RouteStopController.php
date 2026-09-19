<?php

namespace App\Http\Controllers\Logistics;

use App\Exceptions\PinVerificationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\VerifyDeliveryRequest;
use App\Http\Requests\Logistics\VerifyPickupRequest;
use App\Models\RouteStop;
use App\Services\Logistics\PinVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RouteStopController extends Controller
{
    public function __construct(private readonly PinVerificationService $pins) {}

    public function updateStatus(Request $request, RouteStop $stop): RedirectResponse
    {
        $this->authorizeStop($request, $stop);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', RouteStop::STATUSES)],
        ]);

        try {
            $this->pins->updateStopStatus($stop, $validated['status']);
        } catch (PinVerificationException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Stop status updated.');
    }

    public function verifyPickup(VerifyPickupRequest $request, RouteStop $stop): RedirectResponse
    {
        $this->authorizeStop($request, $stop);

        try {
            $this->pins->verifyPickup(
                $stop,
                $request->itemMatches(),
                $request->input('pin'),
                $request->input('notes'),
            );
        } catch (PinVerificationException $e) {
            throw ValidationException::withMessages(['pin' => $e->getMessage()]);
        }

        $message = $request->itemMatches()
            ? 'Pickup verified. Item collected and seller payout is now READY.'
            : 'Pickup failed. Item remains with the seller and the order requires refund processing.';

        return back()->with('status', $message);
    }

    public function verifyDelivery(VerifyDeliveryRequest $request, RouteStop $stop): RedirectResponse
    {
        $this->authorizeStop($request, $stop);

        try {
            $this->pins->verifyDelivery($stop, $request->input('pin'), $request->input('notes'));
        } catch (PinVerificationException $e) {
            throw ValidationException::withMessages(['pin' => $e->getMessage()]);
        }

        return back()->with('status', 'Delivery verified. Order is now complete.');
    }

    /**
     * The assigned driver (or any admin) may act on a stop.
     */
    private function authorizeStop(Request $request, RouteStop $stop): void
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        $stop->loadMissing('route');

        abort_unless($user->isLogistics() && $stop->route->driver_id === $user->id, 403);
    }
}
