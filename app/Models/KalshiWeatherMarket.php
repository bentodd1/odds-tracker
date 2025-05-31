<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KalshiWeatherMarket extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_ticker',
        'ticker',
        'title',
        'status',
        'close_time',
        'last_updated_at',
        'collected_at',
        'strike_type',
        'floor_strike',
        'cap_strike',
        'single_strike',
        'low_temperature',
        'high_temperature',
        'rules_primary',
        'rules_secondary',
        'weather_temperature_id',
    ];

    protected $casts = [
        'close_time' => 'datetime',
        'last_updated_at' => 'datetime',
        'collected_at' => 'datetime',
        'floor_strike' => 'integer',
        'cap_strike' => 'integer',
        'single_strike' => 'integer',
        'low_temperature' => 'integer',
        'high_temperature' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(KalshiWeatherEvent::class);
    }

    public function states(): HasMany
    {
        return $this->hasMany(KalshiWeatherMarketState::class, 'market_id');
    }

    public function latestState()
    {
        return $this->hasOne(KalshiWeatherMarketState::class, 'market_id')
            ->latest('collected_at');
    }

    public function accuWeatherPredictions(): HasMany
    {
        return $this->hasMany(AccuWeatherPrediction::class);
    }

    public function actualTemperature()
    {
        return $this->belongsTo(WeatherTemperature::class, 'weather_temperature_id');
    }
} 