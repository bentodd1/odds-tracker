<?php

namespace App\Console\Commands;

use App\Models\AccuWeatherPrediction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RecordActualWeather extends Command
{
    protected $signature = 'weather:record-actual {--date= : The date to fetch historical data for (Y-m-d format)}';
    protected $description = 'Record actual weather data for past predictions';

    protected $accuWeatherLocationKeys = [
        'Austin' => '351193',      // Austin-Bergstrom International Airport (AUS)
        'Denver' => '2626644',     // Denver International Airport (DEN)
        'Chicago' => '2626577',    // Chicago O'Hare International Airport (ORD)
        'Los Angeles' => '2142541', // Los Angeles International Airport (LAX)
        'New York' => '2627448',   // New York (Central Park)
        'Philadelphia' => '350540', // Philadelphia International Airport (PHL)
        'Miami' => '347936'        // Miami International Airport (MIA)
    ];

    public function handle()
    {
        $apiKey = env('ACCUWEATHER_API_KEY');
        if (!$apiKey) {
            $this->error('AccuWeather API key not found in .env file.');
            return;
        }

        // Get the date to process (either from command option or yesterday)
        $date = $this->option('date') 
            ? Carbon::parse($this->option('date'))->setTimezone('America/Chicago')
            : Carbon::now()->setTimezone('America/Chicago')->subDay();

        // Get predictions that need actual data for the specified date
        $predictions = AccuWeatherPrediction::whereNull('actual_high')
            ->whereNull('actual_low')
            ->whereDate('target_date', $date)
            ->get();

        if ($predictions->isEmpty()) {
            $this->info("No predictions found for date: {$date->format('Y-m-d')}");
            return;
        }

        foreach ($predictions as $prediction) {
            $locationKey = $this->accuWeatherLocationKeys[$prediction->city] ?? null;
            if (!$locationKey) {
                $this->error("No location key found for {$prediction->city}");
                continue;
            }

            // Fetch historical daily data
            $url = "http://dataservice.accuweather.com/currentconditions/v1/{$locationKey}/historical/{$date->format('Y-m-d')}?apikey={$apiKey}&details=true";
            $response = file_get_contents($url);
            
            if ($response === false) {
                $this->error("Failed to fetch historical data for {$prediction->city}");
                continue;
            }

            $data = json_decode($response, true);
            if (!empty($data)) {
                // Get the highest and lowest temperatures from the historical data
                $temperatures = array_map(function($reading) {
                    return $reading['Temperature']['Imperial']['Value'];
                }, $data);

                $actualHigh = max($temperatures);
                $actualLow = min($temperatures);

                // Update the prediction with actual data
                $prediction->actual_high = $actualHigh;
                $prediction->actual_low = $actualLow;
                $prediction->high_difference = $prediction->predicted_high - $actualHigh;
                $prediction->low_difference = $prediction->predicted_low - $actualLow;
                $prediction->save();

                $this->info("Updated actual weather data for {$prediction->city} on {$date->format('Y-m-d')}");
            }
        }
    }
} 