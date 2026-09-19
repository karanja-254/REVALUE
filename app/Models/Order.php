<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_FAILED = 'failed';

    public const PAYMENT_REFUNDED = 'refunded';

    /** @var list<string> */
    public const PAYMENT_STATUSES = [
        self::PAYMENT_PENDING,
        self::PAYMENT_PAID,
        self::PAYMENT_FAILED,
        self::PAYMENT_REFUNDED,
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
        ];
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
}
