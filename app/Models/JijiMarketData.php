<?php

namespace App\Models;

use Database\Factories\JijiMarketDataFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JijiMarketData extends Model
{
    /** @use HasFactory<JijiMarketDataFactory> */
    use HasFactory;

    protected $fillable = ['category', 'condition', 'price', 'source_url', 'scraped_at'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'scraped_at' => 'datetime',
        ];
    }
}
