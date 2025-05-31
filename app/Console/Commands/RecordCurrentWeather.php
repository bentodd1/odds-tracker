<?php

namespace App\Console\Commands;

use App\Models\AccuWeatherPrediction;
use App\Models\WeatherTemperature;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use DOMDocument;
use DOMXPath;

class RecordCurrentWeather extends Command
{
    protected $signature = 'weather:record-current {date?}';
    protected $description = 'Record weather data for all cities. Defaults to yesterday if no date provided.';

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
        // Get the target date, defaulting to yesterday
        $targetDate = $this->argument('date') 
            ? Carbon::parse($this->argument('date'))->setTimezone('America/Chicago')
            : Carbon::now()->setTimezone('America/Chicago')->subDay();

        $targetDay = $targetDate->day;
        $targetDateStr = $targetDate->format('Y-m-d');

        $this->info("Processing weather data for {$targetDateStr}");

        foreach ($this->nwsStations as $city => $station) {
            // Get all predictions for this city and date
            $predictions = AccuWeatherPrediction::where([
                'city' => $city,
                'target_date' => $targetDateStr
            ])->get();

            if ($predictions->isEmpty()) {
                $this->info("No predictions found for {$city} on {$targetDateStr}");
                continue;
            }

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
                WeatherTemperature::updateOrCreate(
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

                // Update all predictions for this city and date
                foreach ($predictions as $prediction) {
                    // Update high if needed
                    if ($currentHigh !== null && (!isset($prediction->actual_high) || $currentHigh > $prediction->actual_high)) {
                        $prediction->actual_high = $currentHigh;
                    }

                    // Update low if needed
                    if ($currentLow !== null && (!isset($prediction->actual_low) || $currentLow < $prediction->actual_low)) {
                        $prediction->actual_low = $currentLow;
                    }

                    // If we have predicted values, calculate differences
                    if (isset($prediction->predicted_high) && $currentHigh !== null) {
                        $prediction->high_difference = $prediction->predicted_high - $prediction->actual_high;
                    }
                    if (isset($prediction->predicted_low) && $currentLow !== null) {
                        $prediction->low_difference = $prediction->predicted_low - $prediction->actual_low;
                    }

                    $prediction->save();
                }
                
                $this->info("Updated weather data for {$city}: High {$currentHigh}°F, Low {$currentLow}°F");
            }
        }
    }
} 