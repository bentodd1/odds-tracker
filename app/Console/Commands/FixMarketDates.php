<?php

namespace App\Console\Commands;

use App\Models\KalshiWeatherMarket;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FixMarketDates extends Command
{
    protected $signature = 'kalshi:fix-dates';
    protected $description = 'Fix incorrect target dates in Kalshi weather markets';

    public function handle()
    {
        $this->info('Starting to fix market dates...');

        // Get all markets with incorrect dates (not in 2025)
        $markets = KalshiWeatherMarket::whereYear('target_date', '!=', 2025)->get();
        
        $this->info("Found {$markets->count()} markets with incorrect dates");

        $fixed = 0;
        $skipped = 0;

        foreach ($markets as $market) {
            try {
                // Extract date from ticker (e.g., KXHIGHLAX-25JUN08 -> 2025-06-08)
                if (preg_match('/-(\d{2}[A-Z]{3}\d{2})$/', $market->ticker, $matches)) {
                    $dateStr = $matches[1];
                    $year = '20' . substr($dateStr, 0, 2);  // Get "25" and make it "2025"
                    $month = substr($dateStr, 2, 3);        // Get "JUN"
                    $day = substr($dateStr, 5, 2);          // Get "08"
                    
                    // Convert month abbreviation to number
                    $monthNum = date('m', strtotime("1 {$month} 2000"));
                    
                    // Create the correct date
                    $correctDate = Carbon::createFromDate($year, $monthNum, $day)->startOfDay();
                    
                    // Update the market
                    $market->target_date = $correctDate;
                    $market->save();
                    
                    $this->info("Fixed market {$market->ticker}: {$market->getOriginal('target_date')} -> {$correctDate->format('Y-m-d')}");
                    $fixed++;
                } else {
                    $this->warn("Could not parse date from ticker: {$market->ticker}");
                    $skipped++;
                }
            } catch (\Exception $e) {
                $this->error("Error processing market {$market->ticker}: " . $e->getMessage());
                $skipped++;
            }
        }

        $this->info("\nSummary:");
        $this->info("- Fixed: {$fixed} markets");
        $this->info("- Skipped: {$skipped} markets");
        
        return 0;
    }
} 