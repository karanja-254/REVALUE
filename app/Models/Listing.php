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
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'suggested_price' => 'decimal:2',
            'final_price' => 'decimal:2',
        ];
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

    public function priceOverrides(): HasMany
    {
        return $this->hasMany(PriceOverride::class);
    }

    public function manualReview(): HasOne
    {
        return $this->hasOne(ManualReview::class);
    }
}
