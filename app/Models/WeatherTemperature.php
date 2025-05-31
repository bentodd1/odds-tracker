<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeatherTemperature extends Model
{
    use HasFactory;

    protected $fillable = [
        'location',
        'date',
        'high_temperature',
        'low_temperature',
        'source',
        'collected_at',
    ];

    protected $casts = [
        'date' => 'date',
        'high_temperature' => 'integer',
        'low_temperature' => 'integer',
        'collected_at' => 'datetime',
    ];

    /**
     * Get the Kalshi weather events for this temperature record.
     */
    public function kalshiEvents()
    {
        return $this->hasMany(KalshiWeatherEvent::class, 'location', 'location')
            ->whereDate('target_date', $this->date);
    }

    /**
     * Get the Kalshi weather markets for this temperature record.
     */
    public function kalshiMarkets()
    {
        return $this->hasManyThrough(
            KalshiWeatherMarket::class,
            KalshiWeatherEvent::class,
            'location', // Foreign key on events table
            'event_id', // Foreign key on markets table
            'location', // Local key on temperatures table
            'id' // Local key on events table
        )->whereDate('target_date', $this->date);
    }

    /**
     * Scope a query to only include records for a specific location.
     */
    public function scopeForLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    /**
     * Scope a query to only include records for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope a query to only include records from a specific source.
     */
    public function scopeFromSource($query, $source)
    {
        return $query->where('source', $source);
    }
} 