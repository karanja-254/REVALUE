<?php

namespace App\Models;

use Database\Factories\DriverLocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The latest known GPS position for a logistics driver.
 *
 * One row per driver (unique user_id), updated in place as the driver's
 * browser reports new coordinates.
 */
class DriverLocation extends Model
{
    /** @use HasFactory<DriverLocationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'heading',
        'accuracy',
        'speed',
        'recorded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'heading' => 'float',
            'accuracy' => 'float',
            'speed' => 'float',
            'recorded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isStale(int $minutes = 15): bool
    {
        return $this->recorded_at === null
            || $this->recorded_at->lt(now()->subMinutes($minutes));
    }
}
