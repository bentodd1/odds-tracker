<?php

namespace App\Console\Commands;

use App\Models\AccuWeatherPrediction;
use App\Models\WeatherTemperature;
use App\Models\KalshiWeatherEvent;
use App\Models\KalshiWeatherMarket;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use DOMDocument;
use DOMXPath;

class RecordCurrentWeather extends Command
{
    protected $signature = 'weather:record-current 
                            {date? : The date to process (default: yesterday)}
                            {--debug : Show debug information}';
    protected $description = 'Record weather data for all cities. Defaults to yesterday if no date provided.';

    protected $debug = false;

    protected $nwsStations = [
        'Austin' => 'KAUS',      // Austin-Bergstrom International Airport
        'Denver' => 'KDEN',      // Denver International Airport
        'Chicago' => 'KORD',     // Chicago O'Hare International Airport
        'Los Angeles' => 'KLAX', // Los Angeles International Airport
        'New York' => 'KNYC',    // New York Central Park
        'Philadelphia' => 'KPHL', // Philadelphia International Airport
        'Miami' => 'KMIA'        // Miami International Airport
    ];

    public function handle()
    {
        $this->debug = $this->option('debug');
        // Get the target date, defaulting to yesterday
        $targetDate = $this->argument('date') 
            ? Carbon::parse($this->argument('date'))->setTimezone('America/Chicago')
            : Carbon::now()->setTimezone('America/Chicago')->subDay();

        $targetDay = $targetDate->day;
        $targetDateStr = $targetDate->format('Y-m-d');

        $this->info("Processing weather data for {$targetDateStr}");

        foreach ($this->nwsStations as $city => $station) {
            // Get predictions for this city and date (but don't require them)
            $predictions = AccuWeatherPrediction::where([
                'city' => $city,
                'target_date' => $targetDateStr
            ])->get();

            // Fetch NWS observation history
            $url = "https://forecast.weather.gov/data/obhistory/{$station}.html";
            $response = Http::get($url);
            
            if (!$response->successful()) {
                $this->error("Failed to fetch NWS data for {$city}");
                continue;
            }

            $html = $response->body();
            
            // Create a new DOMDocument
            $doc = new DOMDocument();
            @$doc->loadHTML($html);
            $xpath = new DOMXPath($doc);
            
            // Find all rows in the observation history table
            $rows = $xpath->query("//table[@class='obs-history']/tbody/tr");
            
            $maxTemps = [];
            $minTemps = [];
            
            foreach ($rows as $row) {
                $cells = $xpath->query('.//td', $row);
                
                // Get the date (first column)
                $date = $cells->item(0)->textContent;
                
                // Only process if the date matches target day
                if ($date == $targetDay) {
                    // Get the max temperature (9th column - Max. in 6 hour section)
                    $maxTemp = $cells->item(8)->textContent;
                    
                    // Get the min temperature (10th column - Min. in 6 hour section)
                    $minTemp = $cells->item(9)->textContent;
                    
                    // Only process if we have valid temperatures
                    if (!empty($maxTemp) && is_numeric($maxTemp)) {
                        $maxTemps[] = floatval($maxTemp);
                    }
                    if (!empty($minTemp) && is_numeric($minTemp)) {
                        $minTemps[] = floatval($minTemp);
                    }
                }
            }
            
            if (!empty($maxTemps) || !empty($minTemps)) {
                $currentHigh = !empty($maxTemps) ? max($maxTemps) : null;
                $currentLow = !empty($minTemps) ? min($minTemps) : null;
                
                // Store in weather_temperatures table
                $temperature = WeatherTemperature::updateOrCreate(
                    [
                        'location' => $city,
                        'date' => $targetDateStr,
                        'source' => 'nws',
                    ],
                    [
                        'high_temperature' => $currentHigh,
                        'low_temperature' => $currentLow,
                        'collected_at' => now(),
                    ]
                );

                // Find all events for this location and date
                $events = KalshiWeatherEvent::where('location', $city)
                    ->whereDate('target_date', $targetDateStr)
                    ->get();

                // Link markets to temperature if we have events
                if (!$events->isEmpty()) {
                    $markets = KalshiWeatherMarket::whereIn('event_id', $events->pluck('id'))
                        ->whereNull('weather_temperature_id')
                        ->where(function($query) {
                            $query->whereNotNull('high_temperature')
                                ->orWhereNotNull('low_temperature');
                        })
                        ->get();

                    foreach ($markets as $market) {
                        $market->weather_temperature_id = $temperature->id;
                        $market->save();
                    }

                    if ($this->debug) {
                        $this->info("Linked {$markets->count()} markets to temperature record");
                    }
                }

                // Update NWS predictions for this city and date
                $nwsPredictions = \App\Models\NwsWeatherPrediction::where('city', $city)
                    ->whereDate('target_date', $targetDateStr)
                    ->get();
                foreach ($nwsPredictions as $nwsPrediction) {
                    if ($currentHigh !== null) {
                        $nwsPrediction->actual_high = $currentHigh;
                        $nwsPrediction->high_difference = $nwsPrediction->predicted_high - $currentHigh;
                    }
                    if ($currentLow !== null) {
                        $nwsPrediction->actual_low = $currentLow;
                        $nwsPrediction->low_difference = $nwsPrediction->predicted_low - $currentLow;
                    }
                    $nwsPrediction->save();
                }
                
                $this->info("Recorded weather data for {$city}: High {$currentHigh}°F, Low {$currentLow}°F");
            } else {
                $this->warn("No temperature data found for {$city} on {$targetDateStr}");
            }
        }
    }
} 