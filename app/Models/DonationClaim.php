<?php

namespace App\Models;

use Database\Factories\DonationClaimFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonationClaim extends Model
{
    /** @use HasFactory<DonationClaimFactory> */
    use HasFactory;

    public const STATUS_CLAIMED = 'claimed';

    public const STATUS_COLLECTED = 'collected';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_CLAIMED,
        self::STATUS_COLLECTED,
        self::STATUS_CANCELLED,
    ];

    /** @var list<string> */
    protected $fillable = [
        'listing_id',
        'organization_id',
        'claimed_by_user_id',
        'status',
        'notes',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }
}
