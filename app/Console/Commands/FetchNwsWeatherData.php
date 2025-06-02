<?php

namespace App\Console\Commands;

use App\Models\KalshiWeatherEvent;
use App\Models\NwsWeatherPrediction;
use App\Services\NationalWeatherService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchNwsWeatherData extends Command
{
    protected $signature = 'weather:fetch-nws
                            {--date= : The date to fetch temperatures for (default: tomorrow)}
                            {--location= : The specific location to fetch (default: all locations)}
                            {--debug : Show debug information}';

    protected $description = 'Fetch and store NWS temperature predictions hourly';

    protected $nws;
    protected $debug = false;

    public function __construct(NationalWeatherService $nws)
    {
        parent::__construct();
        $this->nws = $nws;
    }

    public function handle()
    {
        $this->debug = $this->option('debug');
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now()->addDay();
        $location = $this->option('location');

        // Supported cities for NWS
        $supportedCities = [
            'Chicago',
            'Denver',
            'Los Angeles',
            'New York',
            'Austin',
            'Miami',
            'Philadelphia',
        ];

        $cities = $location ? [$location] : $supportedCities;
        $predictionsStored = 0;
        $currentHour = now()->hour;

        foreach ($cities as $city) {
            if ($this->debug) {
                $this->info("Processing location: {$city}");
            }

            // Get temperature forecast from NWS
            $forecast = $this->nws->getDailyForecast($city, $date);

            if ($this->debug) {
                $this->info("Raw NWS API Response for {$city} on {$date->format('Y-m-d')}:");
                $this->info(json_encode($forecast, JSON_PRETTY_PRINT));
            }

            if (!$forecast) {
                if ($this->debug) {
                    $this->warn("No forecast found for {$city} on {$date->format('Y-m-d')}");
                }
                continue;
            }

            // Try to find a Kalshi event/market for this city/date
            $kalshiEvent = \App\Models\KalshiWeatherEvent::where('location', $city)
                ->whereDate('target_date', $date)
                ->first();
            $kalshiMarketId = $kalshiEvent?->markets->first()?->id;

            // Create a new prediction record
            \App\Models\NwsWeatherPrediction::create([
                'city' => $city,
                'target_date' => $date,
                'forecast_hour' => $currentHour,
                'prediction_date' => now()->toDateString(),
                'prediction_time' => now(),
                'predicted_high' => $forecast['high_temperature'],
                'predicted_low' => $forecast['low_temperature'],
                'kalshi_weather_market_id' => $kalshiMarketId,
            ]);

            $predictionsStored++;
        }

        if ($this->debug) {
            $this->info("✓ Stored {$predictionsStored} NWS predictions");
        }
        return 0;
    }
} 