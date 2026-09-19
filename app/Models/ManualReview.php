<?php

namespace App\Models;

use Database\Factories\ManualReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualReview extends Model
{
    /** @use HasFactory<ManualReviewFactory> */
    use HasFactory;

    protected $fillable = ['listing_id', 'assigned_admin_id', 'status', 'notes'];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }
}
