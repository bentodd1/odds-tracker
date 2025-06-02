<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NwsWeatherPrediction extends Model
{
    protected $fillable = [
        'city',
        'prediction_date',
        'prediction_time',
        'target_date',
        'predicted_high',
        'predicted_low',
        'actual_high',
        'actual_low',
        'high_difference',
        'low_difference',
        'kalshi_weather_market_id',
        'forecast_hour', // The hour of the day when this forecast was made
    ];

    protected $casts = [
        'prediction_date' => 'date',
        'target_date' => 'date',
        'prediction_time' => 'datetime',
        'predicted_high' => 'integer',
        'predicted_low' => 'integer',
        'actual_high' => 'integer',
        'actual_low' => 'integer',
        'high_difference' => 'integer',
        'low_difference' => 'integer',
        'forecast_hour' => 'integer',
    ];

    public function kalshiWeatherMarket(): BelongsTo
    {
        return $this->belongsTo(KalshiWeatherMarket::class);
    }
} 