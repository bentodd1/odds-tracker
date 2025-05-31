<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KalshiWeatherMarketState extends Model
{
    use HasFactory;

    protected $fillable = [
        'market_id',
        'status',
        'close_time',
        'yes_ask',
        'yes_bid',
        'no_ask',
        'no_bid',
        'volume',
        'open_interest',
        'liquidity',
        'last_price',
        'collected_at',
    ];

    protected $casts = [
        'close_time' => 'datetime',
        'collected_at' => 'datetime',
        'yes_ask' => 'decimal:2',
        'yes_bid' => 'decimal:2',
        'no_ask' => 'decimal:2',
        'no_bid' => 'decimal:2',
        'volume' => 'decimal:2',
        'open_interest' => 'decimal:2',
        'liquidity' => 'decimal:2',
        'last_price' => 'decimal:2',
    ];

    public function market(): BelongsTo
    {
        return $this->belongsTo(KalshiWeatherMarket::class);
    }
} 