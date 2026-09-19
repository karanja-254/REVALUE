<?php

namespace App\Models;

use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory;

    public const TYPE_SELL = 'sell';

    public const TYPE_DONATE = 'donate';

    public const TYPE_RECYCLE = 'recycle';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_SELL,
        self::TYPE_DONATE,
        self::TYPE_RECYCLE,
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_SOLD = 'sold';

    public const STATUS_DONATED = 'donated';

    public const STATUS_RECYCLED = 'recycled';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_AVAILABLE,
        self::STATUS_SOLD,
        self::STATUS_DONATED,
        self::STATUS_RECYCLED,
        self::STATUS_CANCELLED,
    ];

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'category',
        'condition',
        'image_path',
        'suggested_price',
        'final_price',
        'status',
        // AI / pricing (Person 3).
        'processing_status',
        'ai_metadata',
        // Pickup location (Maps/Logistics, Person 4).
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
        'pickup_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'suggested_price' => 'decimal:2',
            'final_price' => 'decimal:2',
            'ai_metadata' => 'json',
            'pickup_latitude' => 'float',
            'pickup_longitude' => 'float',
        ];
    }

    public function hasPickupLocation(): bool
    {
        return $this->pickup_latitude !== null && $this->pickup_longitude !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function latestOrder(): HasOne
    {
        return $this->hasOne(Order::class)->latestOfMany();
    }

    public function donationClaim(): HasOne
    {
        return $this->hasOne(DonationClaim::class);
    }

    public function priceOverrides(): HasMany
    {
        return $this->hasMany(PriceOverride::class);
    }

    public function manualReview(): HasOne
    {
        return $this->hasOne(ManualReview::class);
    }

    public function isSell(): bool
    {
        return $this->type === self::TYPE_SELL;
    }

    public function isDonate(): bool
    {
        return $this->type === self::TYPE_DONATE;
    }

    public function isRecycle(): bool
    {
        return $this->type === self::TYPE_RECYCLE;
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function displayPrice(): ?string
    {
        $price = $this->final_price ?? $this->suggested_price;

        if ($price === null) {
            return null;
        }

        return 'KSh '.number_format((float) $price);
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return asset('storage/'.$this->image_path);
    }

    public function coverImage(): string
    {
        if ($this->imageUrl()) {
            return $this->imageUrl();
        }

        return match ($this->category) {
            'electronics' => 'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?auto=format&fit=crop&w=900&q=70',
            'furniture' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=900&q=70',
            'appliances' => 'https://images.unsplash.com/photo-1574269909862-7e1d70bb8078?auto=format&fit=crop&w=900&q=70',
            'mattresses' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=900&q=70',
            'office' => 'https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=900&q=70',
            'clothing' => 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?auto=format&fit=crop&w=900&q=70',
            default => 'https://images.unsplash.com/photo-1484154218962-a197022b5858?auto=format&fit=crop&w=900&q=70',
        };
    }
}
