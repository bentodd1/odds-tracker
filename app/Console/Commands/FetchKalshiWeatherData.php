<?php

namespace App\Console\Commands;

use App\Models\KalshiWeatherEvent;
use App\Models\KalshiWeatherMarket;
use App\Models\KalshiWeatherMarketState;
use App\Services\KalshiApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchKalshiWeatherData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kalshi:fetch-weather
                            {event_ticker? : The specific event ticker to fetch (default: fetch all weather events)}
                            {--category= : The specific category ID to fetch}
                            {--debug : Show debug information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Kalshi weather market data';

    /**
     * @var KalshiApiService
     */
    protected $kalshiApi;

    /**
     * @var bool
     */
    protected $debug = false;

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
        $eventTicker = $this->argument('event_ticker');
        $categoryId = $this->option('category');
        
        // Make sure we have our default categories
        $this->kalshiApi->initializeDefaultCategories();
        
        // Get event tickers to process
        if ($eventTicker) {
            $eventTickers = [[
                'ticker' => $eventTicker,
                'category_id' => $this->kalshiApi->findCategoryForEventTicker($eventTicker)
            ]];
        } else {
            $allTickers = $this->kalshiApi->getAllWeatherEventTickers();
            
            if ($categoryId) {
                $eventTickers = array_filter($allTickers, function($tickerData) use ($categoryId) {
                    return $tickerData['category_id'] == $categoryId;
                });
            } else {
                $eventTickers = $allTickers;
            }
        }
        
        $totalMarketsProcessed = 0;
        
        foreach ($eventTickers as $tickerData) {
            $ticker = $tickerData['ticker'];
            $categoryId = $tickerData['category_id'];
            
            $this->info("Fetching markets for event ticker: {$ticker}");
            
            $marketsData = $this->kalshiApi->getMarkets($ticker);
            
            if (empty($marketsData) || !isset($marketsData['markets']) || !is_array($marketsData['markets'])) {
                if ($this->debug) {
                    $this->warn("No markets found for event ticker: {$ticker}");
                }
                continue;
            }

            // Create or update the event
            $event = KalshiWeatherEvent::firstOrCreate(
                ['event_ticker' => $ticker],
                [
                    'category_id' => $categoryId,
                    'target_date' => $this->extractDateFromTicker($ticker),
                    'location' => $this->extractLocationFromTicker($ticker),
                ]
            );
            
            $marketsProcessed = 0;
            
            foreach ($marketsData['markets'] as $marketData) {
                $this->processMarket($marketData, $event);
                $marketsProcessed++;
            }
            
            $totalMarketsProcessed += $marketsProcessed;
            $this->info("✓ Processed {$marketsProcessed} markets for event ticker: {$ticker}");
        }
        
        $this->info("✓ Total markets processed: {$totalMarketsProcessed}");
        
        return 0;
    }
    
    /**
     * Process and store a market.
     *
     * @param array $marketData
     * @param KalshiWeatherEvent $event
     * @return void
     */
    protected function processMarket(array $marketData, KalshiWeatherEvent $event)
    {
        try {
            if ($this->debug) {
                $this->info("Processing market: " . $marketData['ticker']);
                $this->info("Market title: " . $marketData['title']);
            }

            // Extract strike information from the market data
            $strikeInfo = $this->extractStrikeInfo($marketData);

            // Extract temperatures from the title
            $tempInfo = $this->extractTemperaturesFromTitle($marketData['title']);  // Changed from ticker to title
            
            if ($this->debug) {
                $this->info("Extracted temperatures: " . json_encode($tempInfo));
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
                    'location' => $event->location,
                    'target_date' => $event->target_date,
                    'category_id' => $event->category_id,
                ]
            );

            // Create a new market state
            KalshiWeatherMarketState::create([
                'market_id' => $market->id,
                'status' => $marketData['status'],
                'close_time' => $marketData['close_time'],
                'yes_ask' => $marketData['yes_ask'] ?? null,
                'yes_bid' => $marketData['yes_bid'] ?? null,
                'no_ask' => $marketData['no_ask'] ?? null,
                'no_bid' => $marketData['no_bid'] ?? null,
                'volume' => $marketData['volume'] ?? 0,
                'open_interest' => $marketData['open_interest'] ?? 0,
                'liquidity' => $marketData['liquidity'] ?? 0,
                'last_price' => $marketData['last_price'] ?? null,
                'collected_at' => now(),
            ]);

            $this->info("Created new market state for {$marketData['ticker']}");
        } catch (\Exception $e) {
            $tickerName = isset($marketData['ticker']) ? $marketData['ticker'] : 'unknown';
            $this->error("Error processing market {$tickerName}: " . $e->getMessage());
            if ($this->debug) {
                $this->error($e->getTraceAsString());
            }
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

    protected function extractDateFromTicker(string $ticker): Carbon
    {
        // Example ticker: KXHIGHLAX-25MAY30
        // Extract the date part (25MAY30) and convert to Carbon
        if (preg_match('/-(\d{2}[A-Z]{3}\d{2})$/', $ticker, $matches)) {
            $dateStr = $matches[1];
            // Convert to format Carbon can understand: 25MAY30 -> 2025-05-30
            $day = substr($dateStr, 0, 2);
            $month = substr($dateStr, 2, 3);
            $year = '20' . substr($dateStr, 5, 2);
            
            // Convert month abbreviation to number
            $monthNum = date('m', strtotime("1 {$month} 2000"));
            
            return Carbon::createFromDate($year, $monthNum, $day);
        }
        
        throw new \InvalidArgumentException("Invalid ticker format: {$ticker}");
    }

    protected function extractLocationFromTicker(string $ticker): string
    {
        // Example tickers: KXHIGHLAX-25MAY30, KXHIGHNY-25MAY30, KXHIGHPHIL-25MAY30
        // Extract the location code (LAX, NY, PHIL) and convert to full name
        $locationMap = [
            'LAX' => 'Los Angeles, CA',
            'DEN' => 'Denver, CO',
            'NY' => 'New York, NY',
            'CHI' => 'Chicago, IL',
            'AUS' => 'Austin, TX',
            'MIA' => 'Miami, FL',
            'PHIL' => 'Philadelphia, PA'
        ];

        if (preg_match('/KXHIGH([A-Z]{2,4})/', $ticker, $matches)) {
            $code = $matches[1];
            return $locationMap[$code] ?? $code;
        }
        
        throw new \InvalidArgumentException("Invalid ticker format: {$ticker}");
    }
} 