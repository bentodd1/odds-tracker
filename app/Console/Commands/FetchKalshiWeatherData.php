<?php

namespace App\Console\Commands;

use App\Models\KalshiWeatherMarket;
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
            
            $marketsProcessed = 0;
            
            foreach ($marketsData['markets'] as $market) {
                $this->processMarket($market, $ticker, $categoryId);
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
     * @param array $market
     * @param string $eventTicker
     * @param int|null $categoryId
     * @return void
     */
    protected function processMarket(array $market, string $eventTicker, ?int $categoryId)
    {
        try {
            // Create a new record with the current timestamp
            KalshiWeatherMarket::create([
                'category_id' => $categoryId,
                'event_ticker' => $eventTicker,
                'ticker' => $market['ticker'],
                'title' => $market['title'],
                'status' => $market['status'],
                'close_time' => $market['close_time'],
                'yes_ask' => $market['yes_ask'] ?? null,
                'yes_bid' => $market['yes_bid'] ?? null,
                'no_ask' => $market['no_ask'] ?? null,
                'no_bid' => $market['no_bid'] ?? null,
                'volume' => $market['volume'] ?? null,
                'open_interest' => $market['open_interest'] ?? null,
                'liquidity' => $market['liquidity'] ?? null,
                'rules_primary' => $market['rules_primary'] ?? null,
                'last_updated_at' => now(),
                'collected_at' => now(),
            ]);

            $this->info("Created new market record for {$market['ticker']}");
        } catch (\Exception $e) {
            $tickerName = isset($market['ticker']) ? $market['ticker'] : 'unknown';
            $this->error("Error processing market {$tickerName}: " . $e->getMessage());
            if ($this->debug) {
                $this->error($e->getTraceAsString());
            }
        }
    }
} 