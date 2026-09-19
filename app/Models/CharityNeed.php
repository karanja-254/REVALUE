<?php

namespace App\Models;

use Database\Factories\CharityNeedFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharityNeed extends Model
{
    /** @use HasFactory<CharityNeedFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_FULFILLED = 'fulfilled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_FULFILLED,
    ];

    /** @var list<string> */
    protected $fillable = [
        'organization_id',
        'category',
        'quantity',
        'description',
        'status',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
