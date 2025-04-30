<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KalshiWeatherMarket extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'event_ticker',
        'ticker',
        'title',
        'status',
        'close_time',
        'yes_ask',
        'yes_bid',
        'no_ask',
        'no_bid',
        'volume',
        'open_interest',
        'liquidity',
        'rules_primary',
        'last_updated_at',
        'collected_at'
    ];

    protected $casts = [
        'close_time' => 'datetime',
        'last_updated_at' => 'datetime',
        'collected_at' => 'datetime',
        'yes_ask' => 'float',
        'yes_bid' => 'float',
        'no_ask' => 'float',
        'no_bid' => 'float',
        'volume' => 'float',
        'open_interest' => 'float',
        'liquidity' => 'float',
    ];

    /**
     * Get the category that owns the market
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(KalshiWeatherCategory::class, 'category_id');
    }
} 