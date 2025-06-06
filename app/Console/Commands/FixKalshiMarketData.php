<?php

namespace App\Console\Commands;

use App\Models\KalshiWeatherMarket;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FixKalshiMarketData extends Command
{
    protected $signature = 'kalshi:fix-market-data';
    protected $description = 'Fix any data issues in Kalshi weather markets';

    public function handle()
    {
        $this->info('Starting to fix Kalshi market data...');

        // Fix locations
        $markets = KalshiWeatherMarket::all();
        $fixedLocations = 0;
        $fixedDates = 0;

        foreach ($markets as $market) {
            $originalLocation = $market->location;
            $originalDate = $market->target_date;
            
            // Let the model's boot method handle the standardization
            $market->location = KalshiWeatherMarket::standardizeLocation($market->location);
            if ($market->target_date) {
                $market->target_date = Carbon::parse($market->target_date)->format('Y-m-d');
            }
            
            if ($originalLocation !== $market->location) {
                $fixedLocations++;
            }
            if ($originalDate !== $market->target_date) {
                $fixedDates++;
            }
            
            $market->save();
        }

        $this->info("Fixed {$fixedLocations} locations and {$fixedDates} dates.");
        $this->info('Done!');

        return 0;
    }
} 