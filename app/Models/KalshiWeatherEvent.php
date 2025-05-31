<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KalshiWeatherEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'event_ticker',
        'target_date',
        'location',
    ];

    protected $casts = [
        'target_date' => 'date',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(KalshiWeatherCategory::class);
    }

    public function markets(): HasMany
    {
        return $this->hasMany(KalshiWeatherMarket::class, 'event_id');
    }

    public function actualTemperatures()
    {
        return $this->hasMany(WeatherTemperature::class, 'location', 'location')
            ->whereDate('date', $this->target_date);
    }
} 