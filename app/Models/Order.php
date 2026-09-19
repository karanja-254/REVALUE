<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_FAILED = 'failed';

    public const PAYMENT_REFUNDED = 'refunded';

    public const PAYMENT_REFUND_REQUIRED = 'refund_required';

    /** @var list<string> */
    public const PAYMENT_STATUSES = [
        self::PAYMENT_PENDING,
        self::PAYMENT_PAID,
        self::PAYMENT_FAILED,
        self::PAYMENT_REFUNDED,
        self::PAYMENT_REFUND_REQUIRED,
    ];

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PAID = 'paid';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PICKED_UP = 'picked_up';

    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_PICKUP_FAILED = 'pickup_failed';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_PAID,
        self::STATUS_SCHEDULED,
        self::STATUS_PICKED_UP,
        self::STATUS_OUT_FOR_DELIVERY,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_PICKUP_FAILED,
    ];

    /** @var list<string> */
    protected $fillable = [
        'listing_id',
        'buyer_id',
        'item_price',
        'delivery_fee',
        'service_fee',
        'total_amount',
        'payment_reference',
        'payment_status',
        'order_status',
        'pickup_pin',
        'delivery_pin',
        'pickup_verified_at',
        'delivery_verified_at',
        // Delivery location (Maps/Logistics, Person 4).
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_notes',
    ];

    /** @var list<string> */
    protected $hidden = [
        'pickup_pin',
        'delivery_pin',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_price' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'pickup_verified_at' => 'datetime',
            'delivery_verified_at' => 'datetime',
            'delivery_latitude' => 'float',
            'delivery_longitude' => 'float',
        ];
    }

    public function hasDeliveryLocation(): bool
    {
        return $this->delivery_latitude !== null && $this->delivery_longitude !== null;
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function sellerPayout(): HasOne
    {
        return $this->hasOne(SellerPayout::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * The seller (owner of the listing). Resolved through the listing
     * relation; eager-load `listing.user` to avoid extra queries.
     */
    public function seller(): ?User
    {
        return $this->listing?->user;
    }

    public function routeStops(): HasMany
    {
        return $this->hasMany(RouteStop::class);
    }

    public function pickupStop(): HasOne
    {
        return $this->hasOne(RouteStop::class)->where('type', RouteStop::TYPE_PICKUP);
    }

    public function deliveryStop(): HasOne
    {
        return $this->hasOne(RouteStop::class)->where('type', RouteStop::TYPE_DELIVERY);
    }

    /**
     * Ordered list of the logistics milestones for buyer/seller tracking.
     *
     * @return list<array{key: string, label: string, done: bool, current: bool}>
     */
    public function trackingSteps(): array
    {
        $milestones = [
            self::STATUS_PAID => 'Payment confirmed',
            self::STATUS_SCHEDULED => 'Pickup scheduled',
            self::STATUS_PICKED_UP => 'Picked up from seller',
            self::STATUS_OUT_FOR_DELIVERY => 'Out for delivery',
            self::STATUS_COMPLETED => 'Delivered',
        ];

        $currentIndex = array_search($this->order_status, array_keys($milestones), true);

        $steps = [];
        $i = 0;
        foreach ($milestones as $key => $label) {
            $done = $currentIndex !== false && $i < $currentIndex;
            $current = $key === $this->order_status;

            // Completed order: every milestone is done.
            if ($this->order_status === self::STATUS_COMPLETED) {
                $done = true;
                $current = false;
            }

            $steps[] = [
                'key' => $key,
                'label' => $label,
                'done' => $done,
                'current' => $current,
            ];
            $i++;
        }

        return $steps;
    }

    public function isCompleted(): bool
    {
        return $this->order_status === self::STATUS_COMPLETED;
    }

    public function requiresRefund(): bool
    {
        return $this->payment_status === self::PAYMENT_REFUND_REQUIRED;
    }
}
