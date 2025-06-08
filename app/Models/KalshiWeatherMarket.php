<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
        'location',
        'target_date',
        'category_id',
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
        'target_date' => 'date',
        'category_id' => 'integer',
    ];

    // Valid locations and their aliases
    protected static $validLocations = [
        'Austin' => ['Austin', 'Austin, TX'],
        'Denver' => ['Denver', 'Denver, CO'],
        'Chicago' => ['Chicago', 'Chicago, IL'],
        'Los Angeles' => ['Los Angeles', 'Los Angeles, CA', 'LA'],
        'New York' => ['New York', 'New York, NY', 'NYC'],
        'Philadelphia' => ['Philadelphia', 'Philadelphia, PA', 'Philly'],
        'Miami' => ['Miami', 'Miami, FL'],
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Standardize location before saving
        static::saving(function ($market) {
            if ($market->isDirty('location')) {
                $market->location = static::standardizeLocation($market->location);
            }
            
            // Ensure target_date is properly formatted and has the correct year
            if ($market->isDirty('target_date') && $market->target_date) {
                try {
                    // Log the original value for debugging
                    Log::info('Original target_date value: ' . $market->target_date);
                    
                    // Parse the date
                    $date = Carbon::parse($market->target_date);
                    
                    // Force the year to be 2025
                    $date->year(2025);
                    
                    // Log the parsed date for debugging
                    Log::info('Parsed date with corrected year: ' . $date->format('Y-m-d'));
                    
                    // Format back to Y-m-d
                    $market->target_date = $date->format('Y-m-d');
                    
                    // Log the final value for debugging
                    Log::info('Final target_date value: ' . $market->target_date);
                } catch (\Exception $e) {
                    Log::error('Error parsing target_date: ' . $e->getMessage(), [
                        'original_value' => $market->target_date,
                        'exception' => $e
                    ]);
                }
            }
        });
    }

    /**
     * Standardize a location name to its canonical form
     */
    public static function standardizeLocation(string $location): string
    {
        $location = trim($location);
        
        foreach (static::$validLocations as $canonical => $aliases) {
            if (in_array($location, $aliases)) {
                return $canonical;
            }
        }
        
        // If no match found, return the original location
        return $location;
    }

    /**
     * Get all valid canonical location names
     */
    public static function getValidLocations(): array
    {
        return array_keys(static::$validLocations);
    }

    /**
     * Scope a query to only include markets for a specific location
     */
    public function scopeForLocation($query, $location)
    {
        $canonicalLocation = static::standardizeLocation($location);
        return $query->where('location', $canonicalLocation);
    }

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

    public function category(): BelongsTo
    {
        return $this->belongsTo(KalshiWeatherCategory::class);
    }
} 