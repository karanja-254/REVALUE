<?php

namespace App\Services\Logistics;

use App\Models\LogisticsRoute;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Helper for the planned Wednesday / Saturday collection cadence.
 *
 * ReValue batches pickups and deliveries onto two collection days per week to
 * reduce transport costs (see README section 8).
 */
class CollectionSchedule
{
    /**
     * The next planned collection date on/after the given date.
     */
    public function nextCollectionDate(?CarbonInterface $from = null): CarbonImmutable
    {
        $date = CarbonImmutable::parse($from ?? now())->startOfDay();

        for ($i = 0; $i < 7; $i++) {
            if (in_array($date->dayOfWeek, LogisticsRoute::COLLECTION_DAYS, true)) {
                return $date;
            }
            $date = $date->addDay();
        }

        // Unreachable: a collection day always occurs within any 7-day window.
        return $date;
    }

    /**
     * Is the given date one of the planned collection days?
     */
    public function isCollectionDay(CarbonInterface $date): bool
    {
        return in_array(
            CarbonImmutable::parse($date)->dayOfWeek,
            LogisticsRoute::COLLECTION_DAYS,
            true
        );
    }

    /**
     * The next N upcoming collection dates, starting from today.
     *
     * @return list<CarbonImmutable>
     */
    public function upcomingCollectionDates(int $count = 4, ?CarbonInterface $from = null): array
    {
        $dates = [];
        $cursor = $this->nextCollectionDate($from);

        while (count($dates) < $count) {
            $dates[] = $cursor;
            $cursor = $this->nextCollectionDate($cursor->addDay());
        }

        return $dates;
    }
}
