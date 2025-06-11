<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NwsWeatherPrediction;
use App\Models\WeatherTemperature;

class BackfillNwsActuals extends Command
{
    protected $signature = 'weather:backfill-nws-actuals';
    protected $description = 'Backfill NWS predictions with actuals from weather_temperatures table';

    public function handle()
    {
        $temperatures = WeatherTemperature::where('source', 'nws')->get();
        $updatedCount = 0;
        foreach ($temperatures as $temperature) {
            $nwsPredictions = NwsWeatherPrediction::where('city', $temperature->location)
                ->whereDate('target_date', $temperature->date)
                ->get();
            foreach ($nwsPredictions as $nwsPrediction) {
                if ($temperature->high_temperature !== null) {
                    $nwsPrediction->actual_high = $temperature->high_temperature;
                    $nwsPrediction->high_difference = $nwsPrediction->predicted_high - $temperature->high_temperature;
                }
                if ($temperature->low_temperature !== null) {
                    $nwsPrediction->actual_low = $temperature->low_temperature;
                    $nwsPrediction->low_difference = $nwsPrediction->predicted_low - $temperature->low_temperature;
                }
                $nwsPrediction->save();
                $updatedCount++;
            }
        }
        $this->info("✓ Backfilled {$updatedCount} NWS predictions with actuals from weather_temperatures.");
    }
} 