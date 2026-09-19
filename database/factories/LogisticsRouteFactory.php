<?php

namespace Database\Factories;

use App\Models\LogisticsRoute;
use App\Services\Logistics\CollectionSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LogisticsRoute>
 */
class LogisticsRouteFactory extends Factory
{
    protected $model = LogisticsRoute::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = app(CollectionSchedule::class)->nextCollectionDate();

        return [
            'name' => $date->format('l, M j').' collection run',
            'collection_date' => $date->toDateString(),
            'driver_id' => null,
            'status' => LogisticsRoute::STATUS_PLANNED,
            'notes' => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => LogisticsRoute::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => LogisticsRoute::STATUS_COMPLETED,
            'started_at' => now()->subHours(3),
            'completed_at' => now(),
        ]);
    }
}
