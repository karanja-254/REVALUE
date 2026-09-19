<?php

namespace App\Models;

use Database\Factories\PriceOverrideFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceOverride extends Model
{
    /** @use HasFactory<PriceOverrideFactory> */
    use HasFactory;

    protected $fillable = ['listing_id', 'admin_id', 'old_price', 'new_price', 'reason', 'override_at'];

    protected function casts(): array
    {
        return [
            'old_price' => 'decimal:2',
            'new_price' => 'decimal:2',
            'override_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
