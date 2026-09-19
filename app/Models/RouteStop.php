<?php

namespace App\Models;

use Database\Factories\RouteStopFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An ordered pickup or delivery stop on a {@see LogisticsRoute}.
 *
 * The physical coordinates/address are derived from the related listing
 * (pickup) or order (delivery) rather than duplicated on the stop.
 */
class RouteStop extends Model
{
    /** @use HasFactory<RouteStopFactory> */
    use HasFactory;

    public const TYPE_PICKUP = 'pickup';

    public const TYPE_DELIVERY = 'delivery';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_PICKUP,
        self::TYPE_DELIVERY,
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_EN_ROUTE = 'en_route';

    public const STATUS_ARRIVED = 'arrived';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_EN_ROUTE,
        self::STATUS_ARRIVED,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_SKIPPED,
    ];

    public const RESULT_MATCHED = 'matched';

    public const RESULT_MISMATCH = 'mismatch';

    /** @var list<string> */
    protected $fillable = [
        'route_id',
        'order_id',
        'type',
        'sequence',
        'status',
        'verification_result',
        'verification_notes',
        'evidence_path',
        'arrived_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'arrived_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(LogisticsRoute::class, 'route_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isPickup(): bool
    {
        return $this->type === self::TYPE_PICKUP;
    }

    public function isDelivery(): bool
    {
        return $this->type === self::TYPE_DELIVERY;
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_SKIPPED,
        ], true);
    }

    /**
     * Latitude for this stop, derived from the pickup (listing) or delivery
     * (order) location. Null when the source location has not been set yet.
     */
    protected function latitude(): Attribute
    {
        return Attribute::get(function (): ?float {
            $value = $this->isPickup()
                ? $this->order?->listing?->pickup_latitude
                : $this->order?->delivery_latitude;

            return $value !== null ? (float) $value : null;
        });
    }

    protected function longitude(): Attribute
    {
        return Attribute::get(function (): ?float {
            $value = $this->isPickup()
                ? $this->order?->listing?->pickup_longitude
                : $this->order?->delivery_longitude;

            return $value !== null ? (float) $value : null;
        });
    }

    protected function address(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->isPickup()
            ? $this->order?->listing?->pickup_address
            : $this->order?->delivery_address);
    }

    protected function contactName(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->isPickup()
            ? $this->order?->listing?->user?->name
            : $this->order?->buyer?->name);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
