<?php

namespace App\Console\Commands;

use App\Models\KalshiWeatherEvent;
use App\Models\KalshiWeatherMarket;
use App\Models\KalshiWeatherMarketState;
use App\Services\KalshiApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillKalshiWeatherData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kalshi:backfill-weather
                            {start_date : Start date in Y-m-d format (e.g., 2024-07-20)}
                            {end_date : End date in Y-m-d format (e.g., 2024-07-25)}
                            {--series_ticker= : Specific series ticker to backfill (e.g., KXHIGHNY)}
                            {--debug : Show debug information}
                            {--dry-run : Show what would be processed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill historical Kalshi weather market data and candlestick data';

    /**
     * @var KalshiApiService
     */
    protected $kalshiApi;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var bool
     */
    protected $dryRun = false;

    /**
     * Create a new command instance.
     *
     * @param KalshiApiService $kalshiApi
     */
    public function __construct(KalshiApiService $kalshiApi)
    {
        parent::__construct();
        $this->kalshiApi = $kalshiApi;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->debug = $this->option('debug');
        $this->dryRun = $this->option('dry-run');
        $startDate = $this->argument('start_date');
        $endDate = $this->argument('end_date');
        $seriesTicker = $this->option('series_ticker');

        // Validate dates
        try {
            $startCarbon = Carbon::parse($startDate);
            $endCarbon = Carbon::parse($endDate);
        } catch (\Exception $e) {
            $this->error("Invalid date format. Please use Y-m-d format (e.g., 2024-07-20)");
            return 1;
        }

        if ($startCarbon->gt($endCarbon)) {
            $this->error("Start date must be before or equal to end date");
            return 1;
        }

        // Make sure we have our default categories
        $this->kalshiApi->initializeDefaultCategories();

        // Get series tickers to process
        if ($seriesTicker) {
            $seriesTickers = [$seriesTicker];
        } else {
            $seriesTickers = $this->getAllSeriesTickers();
        }

        $this->info("Starting backfill from {$startDate} to {$endDate}");
        if ($this->dryRun) {
            $this->warn("DRY RUN MODE - No changes will be made");
        }

        $totalMarketsProcessed = 0;
        $totalCandlesticksProcessed = 0;

        foreach ($seriesTickers as $ticker) {
            $this->info("Processing series ticker: {$ticker}");
            
            $currentDate = $startCarbon->copy();
            while ($currentDate->lte($endCarbon)) {
                $dateStr = $currentDate->format('Y-m-d');
                $this->info("  Processing date: {$dateStr}");

                // Fetch markets for this date
                $marketsData = $this->kalshiApi->getMarketsByDate($ticker, $dateStr, $dateStr);

                if (empty($marketsData) || !isset($marketsData['markets']) || !is_array($marketsData['markets'])) {
                    if ($this->debug) {
                        $this->warn("    No markets found for {$ticker} on {$dateStr}");
                    }
                    $currentDate->addDay();
                    continue;
                }

                // Process each market
                foreach ($marketsData['markets'] as $marketData) {
                    $marketsProcessed = $this->processHistoricalMarket($marketData, $ticker, $currentDate);
                    $totalMarketsProcessed += $marketsProcessed;

                    if ($marketsProcessed > 0) {
                        // Fetch candlestick data for this market
                        $candlesticksProcessed = $this->processCandlesticks($marketData, $ticker, $currentDate);
                        $totalCandlesticksProcessed += $candlesticksProcessed;
                    }
                }

                $currentDate->addDay();
            }
        }

        $this->info("✓ Backfill completed!");
        $this->info("✓ Total markets processed: {$totalMarketsProcessed}");
        $this->info("✓ Total candlesticks processed: {$totalCandlesticksProcessed}");

        return 0;
    }

    /**
     * Get all series tickers from categories
     *
     * @return array
     */
    protected function getAllSeriesTickers(): array
    {
        $categories = \App\Models\KalshiWeatherCategory::all();
        $tickers = [];

        foreach ($categories as $category) {
            if ($category->event_prefix) {
                $tickers[] = $category->event_prefix;
            }
        }

        return $tickers;
    }

    /**
     * Process and store a historical market
     *
     * @param array $marketData
     * @param string $seriesTicker
     * @param Carbon $targetDate
     * @return int
     */
    protected function processHistoricalMarket(array $marketData, string $seriesTicker, Carbon $targetDate): int
    {
        try {
            if ($this->debug) {
                $this->info("    Processing market: " . $marketData['ticker']);
                $this->info("    Market title: " . $marketData['title']);
            }

            // Find the category for this series ticker
            $category = \App\Models\KalshiWeatherCategory::where('event_prefix', $seriesTicker)->first();
            if (!$category) {
                if ($this->debug) {
                    $this->warn("    No category found for series ticker: {$seriesTicker}");
                }
                return 0;
            }

            // Extract strike information from the market data
            $strikeInfo = $this->extractStrikeInfo($marketData);

            // Extract temperatures from the title
            $tempInfo = $this->extractTemperaturesFromTitle($marketData['title']);

            // Ensure location is standardized
            $location = KalshiWeatherMarket::standardizeLocation($category->location);

            // Create or update the event
            $event = KalshiWeatherEvent::firstOrCreate(
                ['event_ticker' => $seriesTicker . '-' . $targetDate->format('yM') . $targetDate->format('d')],
                [
                    'category_id' => $category->id,
                    'target_date' => $targetDate->format('Y-m-d'),
                    'location' => $location,
                ]
            );

            if ($this->dryRun) {
                $this->info("    [DRY RUN] Would create/update market: {$marketData['ticker']}");
                return 1;
            }

            // Create or update the market definition
            $market = KalshiWeatherMarket::firstOrCreate(
                [
                    'event_id' => $event->id,
                    'ticker' => $marketData['ticker'],
                ],
                [
                    'event_ticker' => $event->event_ticker,
                    'title' => $marketData['title'],
                    'status' => $marketData['status'],
                    'close_time' => $marketData['close_time'],
                    'last_updated_at' => now(),
                    'collected_at' => now(),
                    'strike_type' => $strikeInfo['type'],
                    'floor_strike' => $strikeInfo['floor_strike'],
                    'cap_strike' => $strikeInfo['cap_strike'],
                    'single_strike' => $strikeInfo['single_strike'],
                    'low_temperature' => $tempInfo['low_temperature'],
                    'high_temperature' => $tempInfo['high_temperature'],
                    'rules_primary' => $marketData['rules_primary'] ?? null,
                    'rules_secondary' => $marketData['rules_secondary'] ?? null,
                    'location' => $location,
                    'target_date' => $targetDate->format('Y-m-d'),
                    'category_id' => $category->id,
                ]
            );

            return 1;
        } catch (\Exception $e) {
            $tickerName = isset($marketData['ticker']) ? $marketData['ticker'] : 'unknown';
            $this->error("    Error processing market {$tickerName}: " . $e->getMessage());
            if ($this->debug) {
                $this->error($e->getTraceAsString());
            }
            return 0;
        }
    }

    /**
     * Process candlestick data for a market
     *
     * @param array $marketData
     * @param string $seriesTicker
     * @param Carbon $targetDate
     * @return int
     */
    protected function processCandlesticks(array $marketData, string $seriesTicker, Carbon $targetDate): int
    {
        try {
            // In dry-run mode, we don't need to find the market since it wasn't created
            if ($this->dryRun) {
                // Calculate time range for candlesticks (24 hours before the market's target date)
                $marketTargetDate = $this->extractDateFromMarketTicker($marketData['ticker']);
                if (!$marketTargetDate) {
                    if ($this->debug) {
                        $this->warn("    [DRY RUN] Could not extract target date from market ticker: {$marketData['ticker']}");
                    }
                    return 0;
                }
                
                // Look for candlestick data from 24 hours before the market's target date to the target date
                $startTime = $marketTargetDate->copy()->subDay()->startOfDay();
                $endTime = $marketTargetDate->copy()->endOfDay();

                $startTs = $startTime->timestamp;
                $endTs = $endTime->timestamp;

                if ($this->debug) {
                    $this->info("    [DRY RUN] Would fetch candlesticks for {$marketData['ticker']} from {$startTime->toDateTimeString()} to {$endTime->toDateTimeString()}");
                }

                $candlesticksData = $this->kalshiApi->getCandlesticks($seriesTicker, $marketData['ticker'], $startTs, $endTs, 60);

                if (empty($candlesticksData) || !isset($candlesticksData['candlesticks']) || !is_array($candlesticksData['candlesticks'])) {
                    if ($this->debug) {
                        $this->warn("    [DRY RUN] No candlesticks found for {$marketData['ticker']}");
                    }
                    return 0;
                }

                $candlesticksProcessed = 0;
                foreach ($candlesticksData['candlesticks'] as $candlestick) {
                    // Check if the candlestick has the expected structure
                    if (!isset($candlestick['end_period_ts'])) {
                        if ($this->debug) {
                            $this->warn("    [DRY RUN] Candlestick missing end_period_ts: " . json_encode($candlestick));
                        }
                        continue;
                    }
                    $this->info("    [DRY RUN] Would create candlestick state for {$marketData['ticker']} at " . date('Y-m-d H:i:s', $candlestick['end_period_ts']));
                    $candlesticksProcessed++;
                }

                if ($this->debug) {
                    $this->info("    [DRY RUN] Would create {$candlesticksProcessed} candlestick states for {$marketData['ticker']}");
                }

                return $candlesticksProcessed;
            }

            // For real execution, find the market in database
            $market = KalshiWeatherMarket::where('ticker', $marketData['ticker'])->first();
            if (!$market) {
                if ($this->debug) {
                    $this->warn("    Market not found in database: {$marketData['ticker']}");
                }
                return 0;
            }

            // Calculate time range for candlesticks (24 hours before the market's target date)
            // The targetDate here is when we're fetching the data, but we need to look at the market's actual target date
            $marketTargetDate = $this->extractDateFromMarketTicker($marketData['ticker']);
            if (!$marketTargetDate) {
                if ($this->debug) {
                    $this->warn("    Could not extract target date from market ticker: {$marketData['ticker']}");
                }
                return 0;
            }
            
            // Look for candlestick data from 24 hours before the market's target date to the target date
            $startTime = $marketTargetDate->copy()->subDay()->startOfDay();
            $endTime = $marketTargetDate->copy()->endOfDay();

            $startTs = $startTime->timestamp;
            $endTs = $endTime->timestamp;

            if ($this->debug) {
                $this->info("    Fetching candlesticks for {$marketData['ticker']} from {$startTime->toDateTimeString()} to {$endTime->toDateTimeString()}");
            }

            $candlesticksData = $this->kalshiApi->getCandlesticks($seriesTicker, $marketData['ticker'], $startTs, $endTs, 60);

            if (empty($candlesticksData) || !isset($candlesticksData['candlesticks']) || !is_array($candlesticksData['candlesticks'])) {
                if ($this->debug) {
                    $this->warn("    No candlesticks found for {$marketData['ticker']}");
                }
                return 0;
            }

            $candlesticksProcessed = 0;

            foreach ($candlesticksData['candlesticks'] as $candlestick) {
                // Check if the candlestick has the expected structure
                if (!isset($candlestick['end_period_ts'])) {
                    if ($this->debug) {
                        $this->warn("    Candlestick missing end_period_ts: " . json_encode($candlestick));
                    }
                    continue;
                }
                
                // Extract values from the candlestick data structure
                $yesAsk = $candlestick['yes_ask']['close'] ?? null;
                $yesBid = $candlestick['yes_bid']['close'] ?? null;
                $volume = $candlestick['volume'] ?? 0;
                $openInterest = $candlestick['open_interest'] ?? 0;
                $lastPrice = $candlestick['price']['close'] ?? null;
                
                // Create market state from candlestick data
                KalshiWeatherMarketState::create([
                    'market_id' => $market->id,
                    'status' => $marketData['status'],
                    'close_time' => $marketData['close_time'],
                    'yes_ask' => $yesAsk,
                    'yes_bid' => $yesBid,
                    'no_ask' => null, // Not available in candlestick data
                    'no_bid' => null, // Not available in candlestick data
                    'volume' => $volume,
                    'open_interest' => $openInterest,
                    'liquidity' => 0, // Not available in candlestick data
                    'last_price' => $lastPrice,
                    'collected_at' => Carbon::createFromTimestamp($candlestick['end_period_ts']),
                ]);

                $candlesticksProcessed++;
            }

            if ($this->debug) {
                $this->info("    Created {$candlesticksProcessed} candlestick states for {$marketData['ticker']}");
            }

            return $candlesticksProcessed;
        } catch (\Exception $e) {
            $tickerName = isset($marketData['ticker']) ? $marketData['ticker'] : 'unknown';
            $this->error("    Error processing candlesticks for {$tickerName}: " . $e->getMessage());
            if ($this->debug) {
                $this->error($e->getTraceAsString());
            }
            return 0;
        }
    }

    protected function extractStrikeInfo(array $marketData): array
    {
        // Determine strike type from title
        $title = strtolower($marketData['title']);
        $strikeType = 'between';
        
        if (str_contains($title, 'less than') || str_contains($title, '<')) {
            $strikeType = 'less';
        } elseif (str_contains($title, 'greater than') || str_contains($title, '>')) {
            $strikeType = 'greater';
        }

        $floorStrike = null;
        $capStrike = null;
        $singleStrike = null;

        // Extract strike values based on type
        switch ($strikeType) {
            case 'between':
                // For between markets, try to extract both floor and cap
                if (preg_match('/between (\d+)-(\d+)/', $title, $matches)) {
                    $floorStrike = (int)$matches[1];
                    $capStrike = (int)$matches[2];
                }
                break;
            
            case 'less':
            case 'greater':
                // For less/greater than markets, extract single strike value
                if (preg_match('/(?:less than|greater than|>|<) (\d+)/', $title, $matches)) {
                    $singleStrike = (int)$matches[1];
                }
                break;
        }

        return [
            'type' => $strikeType,
            'floor_strike' => $floorStrike,
            'cap_strike' => $capStrike,
            'single_strike' => $singleStrike,
        ];
    }

    protected function extractTemperaturesFromTitle(string $title): array
    {
        $lowTemp = null;
        $highTemp = null;

        // Check the title for explicit temperature mentions
        if (preg_match('/high temp.*?(?:be\s*)?(?:<|less than)\s*(\d+)/i', $title, $matches)) {
            // High temp less than case (e.g., "high temp in Miami be <87°")
            $highTemp = (int)$matches[1];
            return [
                'low_temperature' => null,
                'high_temperature' => $highTemp,
            ];
        } elseif (preg_match('/high temp.*?(?:be\s*)?(?:>|greater than)\s*(\d+)/i', $title, $matches)) {
            // High temp greater than case (e.g., "high temp in Miami be >93°")
            $highTemp = (int)$matches[1];
            return [
                'low_temperature' => null,
                'high_temperature' => $highTemp,
            ];
        } elseif (preg_match('/high temp.*?be\s*(\d+)-(\d+)/i', $title, $matches)) {
            // Between market (e.g., "high temp in Philadelphia be 76-77°")
            $lowTemp = (int)$matches[1];
            $highTemp = (int)$matches[2];
            return [
                'low_temperature' => $lowTemp,
                'high_temperature' => $highTemp,
            ];
        }

        // Then check the ticker for market type indicators
        if (preg_match('/-B(\d+\.?\d*)-(\d+\.?\d*)$/', $title, $matches)) {
            // Between market (e.g., B63.5-64.5)
            $lowTemp = (int)floor((float)$matches[1]);  // Round down for low
            $highTemp = (int)ceil((float)$matches[2]);  // Round up for high
        } elseif (preg_match('/-B(\d+\.?\d*)$/', $title, $matches)) {
            // Single temperature between market
            $temp = (int)floor((float)$matches[1]);
            $lowTemp = $temp;
            $highTemp = $temp + 1;
        } elseif (preg_match('/-G(\d+\.?\d*)$/', $title, $matches)) {
            // Greater than market (e.g., G75)
            $highTemp = (int)ceil((float)$matches[1]);
        } elseif (preg_match('/-L(\d+\.?\d*)$/', $title, $matches)) {
            // Less than market (e.g., L75)
            $highTemp = (int)floor((float)$matches[1]);
        } elseif (preg_match('/-P(\d+\.?\d*)$/', $title, $matches)) {
            // Plus market (e.g., P75+)
            $lowTemp = (int)floor((float)$matches[1]);
        }

        return [
            'low_temperature' => $lowTemp,
            'high_temperature' => $highTemp,
        ];
    }

    /**
     * Extract the target date from a market ticker
     *
     * @param string $marketTicker
     * @return Carbon|null
     */
    protected function extractDateFromMarketTicker(string $marketTicker): ?Carbon
    {
        // Example ticker: KXHIGHDEN-25JUL21-B96.5
        // Extract the date part (25JUL21) and convert to Carbon
        if (preg_match('/-(\d{2}[A-Z]{3}\d{2})/', $marketTicker, $matches)) {
            $dateStr = $matches[1];
            // Convert to format Carbon can understand
            $year = '20' . substr($dateStr, 0, 2);  // Get "25" and make it "2025"
            $month = substr($dateStr, 2, 3);        // Get "JUL"
            $day = substr($dateStr, 5, 2);          // Get "21"
            // Convert month abbreviation to number
            $monthNum = date('m', strtotime("1 {$month} 2000"));
            // Return a Carbon date
            return Carbon::createFromDate($year, $monthNum, $day)->startOfDay();
        }
        return null;
    }
} 