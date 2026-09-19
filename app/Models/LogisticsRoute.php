<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\LogisticsRouteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A batched collection run on a planned collection day (Wed/Sat).
 *
 * Maps to the shared `routes` table. Named LogisticsRoute (rather than Route)
 * to avoid confusion with Laravel's routing facade.
 */
class LogisticsRoute extends Model
{
    /** @use HasFactory<LogisticsRouteFactory> */
    use HasFactory;

    protected $table = 'routes';

    public const STATUS_PLANNED = 'planned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PLANNED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Planned collection days as Carbon day-of-week values (0=Sun ... 6=Sat).
     *
     * @var list<int>
     */
    public const COLLECTION_DAYS = [
        CarbonImmutable::WEDNESDAY,
        CarbonImmutable::SATURDAY,
    ];

    /** @var list<string> */
    protected $fillable = [
        'name',
        'collection_date',
        'driver_id',
        'status',
        'notes',
        'started_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class, 'route_id')->orderBy('sequence');
    }

    public function pickups(): HasMany
    {
        return $this->stops()->where('type', RouteStop::TYPE_PICKUP);
    }

    public function deliveries(): HasMany
    {
        return $this->stops()->where('type', RouteStop::TYPE_DELIVERY);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_PLANNED, self::STATUS_IN_PROGRESS], true);
    }

    /**
     * Progress percentage across all stops (0-100).
     */
    public function progressPercent(): int
    {
        $total = $this->stops->count();

        if ($total === 0) {
            return 0;
        }

        $done = $this->stops->whereIn('status', [
            RouteStop::STATUS_COMPLETED,
            RouteStop::STATUS_FAILED,
            RouteStop::STATUS_SKIPPED,
        ])->count();

        return (int) round(($done / $total) * 100);
    }
}
