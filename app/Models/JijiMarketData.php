<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JijiMarketData extends Model
{
    protected $fillable = ['category', 'condition', 'price', 'source_url', 'scraped_at'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'scraped_at' => 'datetime',
        ];
    }
}
