<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    public const TYPE_CHARITY = 'charity';

    public const TYPE_RECYCLER = 'recycler';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_CHARITY,
        self::TYPE_RECYCLER,
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_VERIFIED,
        self::STATUS_REJECTED,
    ];

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'contact_person',
        'email',
        'phone',
        'location',
        'registration_details',
        'website',
        'supporting_document_path',
        'verification_status',
        'review_notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function needs(): HasMany
    {
        return $this->hasMany(CharityNeed::class);
    }

    public function donationClaims(): HasMany
    {
        return $this->hasMany(DonationClaim::class);
    }

    public function isVerified(): bool
    {
        return $this->verification_status === self::STATUS_VERIFIED;
    }

    public function isPending(): bool
    {
        return $this->verification_status === self::STATUS_PENDING;
    }

    public function isCharity(): bool
    {
        return $this->type === self::TYPE_CHARITY;
    }

    public function isRecycler(): bool
    {
        return $this->type === self::TYPE_RECYCLER;
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('verification_status', self::STATUS_VERIFIED);
    }

    public function scopeCharities(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_CHARITY);
    }

    public function scopeRecyclers(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_RECYCLER);
    }
}
