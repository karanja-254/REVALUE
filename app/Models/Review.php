<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'order_id',
        'reviewer_id',
        'accurate_description',
        'smooth_delivery',
        'professional_handling',
        'punctual_pickup',
        'comment',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function averageScore(): float
    {
        return round((
            $this->accurate_description
            + $this->smooth_delivery
            + $this->professional_handling
            + $this->punctual_pickup
        ) / 4, 1);
    }
}
