<?php

namespace App\Console\Commands;

use App\Models\AccuWeatherPrediction;
use App\Models\WeatherTemperature;
use Illuminate\Console\Command;

class BackfillAccuWeatherFromTemperatures extends Command
{
    protected $signature = 'weather:backfill-accuweather-from-temperatures';
    protected $description = 'Backfill AccuWeather predictions with actuals from weather_temperatures table';

    public function handle()
    {
        // Get all weather temperature records
        $temperatures = WeatherTemperature::where('source', 'accuweather')->get();
        
        $this->info("Found {$temperatures->count()} weather temperature records");
        
        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($temperatures as $temperature) {
            // Find all AccuWeather predictions for this city and date that don't have actual data
            $predictionsToUpdate = AccuWeatherPrediction::where([
                'city' => $temperature->location,
                'target_date' => $temperature->date,
            ])
            ->where(function($query) {
                $query->whereNull('actual_high')
                    ->orWhereNull('actual_low');
            })
            ->get();

            if ($predictionsToUpdate->isEmpty()) {
                $skippedCount++;
                continue;
            }

            $this->info("Found {$predictionsToUpdate->count()} predictions to update for {$temperature->location} on {$temperature->date}");

            foreach ($predictionsToUpdate as $prediction) {
                // Copy the actual weather data
                if ($temperature->high_temperature !== null) {
                    $prediction->actual_high = $temperature->high_temperature;
                    $prediction->high_difference = $prediction->predicted_high - $temperature->high_temperature;
                }
                if ($temperature->low_temperature !== null) {
                    $prediction->actual_low = $temperature->low_temperature;
                    $prediction->low_difference = $prediction->predicted_low - $temperature->low_temperature;
                }

                $prediction->save();
                $updatedCount++;
            }
        }

        $this->info("Backfill complete!");
        $this->info("Updated {$updatedCount} AccuWeather predictions");
        $this->info("Skipped {$skippedCount} temperature records (no predictions to update)");
    }
} 