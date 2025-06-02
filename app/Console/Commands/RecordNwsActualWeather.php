<?php

namespace App\Console\Commands;

use App\Models\NwsWeatherPrediction;
use App\Models\WeatherTemperature;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RecordNwsActualWeather extends Command
{
    protected $signature = 'weather:record-nws-actual
                            {--date= : The date to fetch historical data for (Y-m-d format)}
                            {--debug : Show debug information}';

    protected $description = 'Record actual weather data for past NWS predictions';

    protected $debug = false;

    public function handle()
    {
        $this->debug = $this->option('debug');
        
        // Get the date to process (either from command option or yesterday)
        $date = $this->option('date') 
            ? Carbon::parse($this->option('date'))->setTimezone('America/Chicago')
            : Carbon::now()->setTimezone('America/Chicago')->subDay();

        // Get predictions that need actual data for the specified date
        $predictions = NwsWeatherPrediction::whereNull('actual_high')
            ->whereNull('actual_low')
            ->whereDate('target_date', $date)
            ->get();

        if ($predictions->isEmpty()) {
            $this->info("No NWS predictions found for date: {$date->format('Y-m-d')}");
            return;
        }

        $updatedCount = 0;

        foreach ($predictions as $prediction) {
            try {
                // Get the actual temperature from our weather_temperatures table
                $actualTemp = WeatherTemperature::where([
                    'location' => $prediction->city,
                    'date' => $prediction->target_date,
                    'source' => 'accuweather' // We're using AccuWeather's actual temperatures as the source of truth
                ])->first();

                if (!$actualTemp) {
                    if ($this->debug) {
                        $this->warn("No actual temperature data found for {$prediction->city} on {$date->format('Y-m-d')}");
                    }
                    continue;
                }

                // Update the prediction with actual data
                $prediction->actual_high = $actualTemp->high_temperature;
                $prediction->actual_low = $actualTemp->low_temperature;
                $prediction->high_difference = $prediction->predicted_high - $actualTemp->high_temperature;
                $prediction->low_difference = $prediction->predicted_low - $actualTemp->low_temperature;
                $prediction->save();

                $updatedCount++;

                if ($this->debug) {
                    $this->info("Updated actual weather data for {$prediction->city} on {$date->format('Y-m-d')} (forecast hour: {$prediction->forecast_hour})");
                }
            } catch (\Exception $e) {
                $this->error("Error processing {$prediction->city}: " . $e->getMessage());
                if ($this->debug) {
                    $this->error($e->getTraceAsString());
                }
            }
        }

        $this->info("✓ Updated {$updatedCount} NWS predictions with actual weather data");
    }
} 