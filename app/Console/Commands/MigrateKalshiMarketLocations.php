<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KalshiWeatherMarket;
use App\Models\KalshiWeatherCategory;

class MigrateKalshiMarketLocations extends Command
{
    protected $signature = 'kalshi:migrate-market-locations';
    protected $description = 'Populate the location and category_id columns for Kalshi weather markets based on title';

    public function handle()
    {
        // Map canonical city names to category slugs
        $cityToSlug = [
            'Austin' => 'austin-temperature',
            'Denver' => 'denver-temperature',
            'Chicago' => 'chicago-temperature',
            'Los Angeles' => 'los-angeles-temperature',
            'New York' => 'nyc-temperature',
            'Philadelphia' => 'philadelphia-temperature',
            'Miami' => 'miami-temperature',
        ];

        // Aliases for matching in the title
        $cityAliases = [
            'Austin' => ['Austin'],
            'Denver' => ['Denver'],
            'Chicago' => ['Chicago'],
            'Los Angeles' => ['Los Angeles', 'LA'],
            'New York' => ['New York', 'NYC'],
            'Philadelphia' => ['Philadelphia', 'Philly'],
            'Miami' => ['Miami'],
        ];

        $updated = 0;

        // Backfill target_date for all markets if missing
        $backfilled = 0;
        $marketsToBackfill = KalshiWeatherMarket::whereNull('target_date')->get();
        foreach ($marketsToBackfill as $market) {
            if ($market->close_time) {
                $market->target_date = \Carbon\Carbon::parse($market->close_time)->subDay()->toDateString();
                $market->save();
                $backfilled++;
            }
        }
        $this->info("Backfilled $backfilled markets with target_date.");

        $markets = KalshiWeatherMarket::where(function($q) {
            $q->whereNull('location')->orWhereNull('category_id');
        })->get();

        foreach ($markets as $market) {
            foreach ($cityAliases as $canonical => $aliases) {
                foreach ($aliases as $alias) {
                    if (stripos($market->title, $alias) !== false) {
                        $market->location = $canonical;

                        // Match category by slug
                        $slug = $cityToSlug[$canonical];
                        $category = KalshiWeatherCategory::where('slug', $slug)->first();
                        if ($category) {
                            $market->category_id = $category->id;
                        }

                        $market->save();
                        $updated++;
                        break 2; // Stop after first match
                    }
                }
            }
        }

        $this->info("Updated $updated markets with location and category_id.");
    }
} 