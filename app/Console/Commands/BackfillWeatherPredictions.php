<?php

namespace App\Console\Commands;

use App\Models\AccuWeatherPrediction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillWeatherPredictions extends Command
{
    protected $signature = 'weather:backfill-predictions';
    protected $description = 'Backfill weather data for predictions that share the same target date and city';

    public function handle()
    {
        // Get all predictions that have actual weather data
        $predictionsWithWeather = AccuWeatherPrediction::whereNotNull('actual_high')
            ->whereNotNull('actual_low')
            ->get();

        $this->info("Found {$predictionsWithWeather->count()} predictions with recorded weather data");

        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($predictionsWithWeather as $sourcePrediction) {
            // Find all predictions for the same city and target date that don't have weather data
            $predictionsToUpdate = AccuWeatherPrediction::where([
                'city' => $sourcePrediction->city,
                'target_date' => $sourcePrediction->target_date,
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

            $this->info("Found {$predictionsToUpdate->count()} predictions to update for {$sourcePrediction->city} on {$sourcePrediction->target_date}");

            foreach ($predictionsToUpdate as $prediction) {
                // Copy the actual weather data
                $prediction->actual_high = $sourcePrediction->actual_high;
                $prediction->actual_low = $sourcePrediction->actual_low;

                // Calculate differences if we have predicted values
                if (isset($prediction->predicted_high)) {
                    $prediction->high_difference = $prediction->predicted_high - $prediction->actual_high;
                }
                if (isset($prediction->predicted_low)) {
                    $prediction->low_difference = $prediction->predicted_low - $prediction->actual_low;
                }

                $prediction->save();
                $updatedCount++;
            }
        }

        $this->info("Backfill complete!");
        $this->info("Updated {$updatedCount} predictions");
        $this->info("Skipped {$skippedCount} predictions (no updates needed)");
    }
} 