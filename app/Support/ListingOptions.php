<?php

namespace App\Support;

class ListingOptions
{
    /** @var list<string> */
    public const CATEGORIES = [
        'furniture',
        'electronics',
        'appliances',
        'mattresses',
        'household',
        'clothing',
        'office',
    ];

    /** @var list<string> */
    public const CONDITIONS = [
        'new',
        'excellent',
        'good',
        'fair',
        'damaged',
    ];

    /**
     * @return array<string, string>
     */
    public static function categoryLabels(): array
    {
        return [
            'furniture' => 'Furniture',
            'electronics' => 'Electronics',
            'appliances' => 'Appliances',
            'mattresses' => 'Mattresses',
            'household' => 'Household',
            'clothing' => 'Clothing',
            'office' => 'Office',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function conditionLabels(): array
    {
        return [
            'new' => 'New',
            'excellent' => 'Excellent',
            'good' => 'Good',
            'fair' => 'Fair',
            'damaged' => 'Damaged / for parts',
        ];
    }
}
