<?php

namespace App\Services\Logistics;

use App\Exceptions\PinVerificationException;
use App\Models\Order;
use App\Models\RouteStop;
use App\Models\SellerPayout;
use Illuminate\Support\Facades\DB;

/**
 * Implements the two-PIN verification system (README sections 6 & 7).
 *
 * Pickup: the logistics driver first confirms the physical item matches the
 * listing. Only then does the seller hand over the 4-digit pickup PIN. A
 * matching PIN moves the order to PICKED_UP and the seller payout to READY.
 * A mismatch fails the pickup and leaves the item with the seller.
 *
 * Delivery: the buyer hands the driver a separate 4-digit delivery PIN on
 * receipt, which moves the order to COMPLETED.
 */
class PinVerificationService
{
    /**
     * Record the result of a doorstep pickup verification.
     *
     * @param  bool  $itemMatches  Whether the physical item matched the listing.
     * @param  string|null  $pin  The seller's pickup PIN (required when the item matches).
     */
    public function verifyPickup(
        RouteStop $stop,
        bool $itemMatches,
        ?string $pin = null,
        ?string $notes = null,
        ?string $evidencePath = null,
    ): RouteStop {
        $this->assertStopType($stop, RouteStop::TYPE_PICKUP);

        $order = $stop->order;

        if (! in_array($order->order_status, [Order::STATUS_PAID, Order::STATUS_SCHEDULED], true)) {
            throw PinVerificationException::invalidState(
                'This order is not awaiting pickup (current status: '.$order->order_status.').'
            );
        }

        return DB::transaction(function () use ($stop, $order, $itemMatches, $pin, $notes, $evidencePath) {
            // Item does not match the listing -> pickup fails, item stays with
            // the seller. The driver must not renegotiate at the doorstep.
            if (! $itemMatches) {
                $order->update(['order_status' => Order::STATUS_PICKUP_FAILED]);

                $stop->update([
                    'status' => RouteStop::STATUS_FAILED,
                    'verification_result' => RouteStop::RESULT_MISMATCH,
                    'verification_notes' => $notes,
                    'evidence_path' => $evidencePath,
                    'arrived_at' => $stop->arrived_at ?? now(),
                    'completed_at' => now(),
                ]);

                return $stop->fresh();
            }

            // Item matches -> the seller now provides the pickup PIN.
            $this->assertPin($pin, $order->pickup_pin);

            $order->update([
                'order_status' => Order::STATUS_PICKED_UP,
                'pickup_verified_at' => now(),
            ]);

            $stop->update([
                'status' => RouteStop::STATUS_COMPLETED,
                'verification_result' => RouteStop::RESULT_MATCHED,
                'verification_notes' => $notes,
                'evidence_path' => $evidencePath,
                'arrived_at' => $stop->arrived_at ?? now(),
                'completed_at' => now(),
            ]);

            $this->markPayoutReady($order);

            return $stop->fresh();
        });
    }

    /**
     * Record a successful delivery via the buyer's delivery PIN.
     */
    public function verifyDelivery(RouteStop $stop, string $pin, ?string $notes = null): RouteStop
    {
        $this->assertStopType($stop, RouteStop::TYPE_DELIVERY);

        $order = $stop->order;

        if (! in_array($order->order_status, [Order::STATUS_PICKED_UP, Order::STATUS_OUT_FOR_DELIVERY], true)) {
            throw PinVerificationException::invalidState(
                'This order is not ready for delivery (current status: '.$order->order_status.').'
            );
        }

        return DB::transaction(function () use ($stop, $order, $pin, $notes) {
            $this->assertPin($pin, $order->delivery_pin);

            $order->update([
                'order_status' => Order::STATUS_COMPLETED,
                'delivery_verified_at' => now(),
            ]);

            $stop->update([
                'status' => RouteStop::STATUS_COMPLETED,
                'verification_notes' => $notes,
                'arrived_at' => $stop->arrived_at ?? now(),
                'completed_at' => now(),
            ]);

            return $stop->fresh();
        });
    }

    /**
     * Move a stop between the transit states (en route / arrived) and keep the
     * order status in sync for delivery legs.
     */
    public function updateStopStatus(RouteStop $stop, string $status): RouteStop
    {
        if (! in_array($status, RouteStop::STATUSES, true)) {
            throw PinVerificationException::invalidState('Unknown stop status: '.$status);
        }

        $attributes = ['status' => $status];

        if ($status === RouteStop::STATUS_ARRIVED) {
            $attributes['arrived_at'] = $stop->arrived_at ?? now();
        }

        $stop->update($attributes);

        // When a delivery leg starts moving, reflect that on the order so the
        // buyer sees "out for delivery".
        if ($stop->isDelivery()
            && in_array($status, [RouteStop::STATUS_EN_ROUTE, RouteStop::STATUS_ARRIVED], true)
            && $stop->order->order_status === Order::STATUS_PICKED_UP) {
            $stop->order->update(['order_status' => Order::STATUS_OUT_FOR_DELIVERY]);
        }

        return $stop->fresh();
    }

    private function assertStopType(RouteStop $stop, string $expected): void
    {
        if ($stop->type !== $expected) {
            throw PinVerificationException::invalidState('This stop is not a '.$expected.' stop.');
        }

        if ($stop->isFinished()) {
            throw PinVerificationException::invalidState('This stop has already been finalised.');
        }
    }

    private function assertPin(?string $provided, ?string $expected): void
    {
        $provided = trim((string) $provided);

        if ($expected === null || $provided === '' || ! hash_equals($expected, $provided)) {
            throw PinVerificationException::invalidPin();
        }
    }

    /**
     * After a successful pickup the seller payout becomes READY for an admin
     * to pay out. We only nudge an existing ledger row (owned by the Payments
     * feature) and never create one here.
     */
    private function markPayoutReady(Order $order): void
    {
        $payout = $order->sellerPayout;

        if ($payout instanceof SellerPayout && $payout->status === SellerPayout::STATUS_PENDING) {
            $payout->update(['status' => SellerPayout::STATUS_READY]);
        }
    }
}
