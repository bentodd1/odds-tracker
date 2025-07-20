<?php

namespace App\Services;

use App\Models\KalshiWeatherCategory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KalshiApiService
{
    protected $baseUrl = 'https://api.elections.kalshi.com/trade-api/v2';
    
    /**
     * Fetch markets for a specific event ticker
     *
     * @param string $eventTicker
     * @return array
     */
    public function getMarkets(string $eventTicker): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/markets", [
                'event_ticker' => $eventTicker,
            ]);
            
            if ($response->successful()) {
                return $response->json() ?? [];
            }
            
            Log::error('Kalshi API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'event_ticker' => $eventTicker
            ]);
            
            return [];
        } catch (\Exception $e) {
            Log::error('Kalshi API exception', [
                'message' => $e->getMessage(),
                'event_ticker' => $eventTicker
            ]);
            
            return [];
        }
    }
    
    /**
     * Get available weather categories with their event prefixes
     * 
     * @return array
     */
    public function getWeatherCategories(): array
    {
        // Return all active categories from the database
        return KalshiWeatherCategory::all()->toArray();
    }
    
    /**
     * Initialize default weather categories if they don't exist
     */
    public function initializeDefaultCategories(): void
    {
        $defaultCategories = [
            [
                'name' => 'Denver Temperature',
                'slug' => 'denver-temperature',
                'description' => 'Temperature forecasts for Denver, Colorado',
                'location' => 'Denver, CO',
                'event_prefix' => 'KXHIGHDEN'
            ],
            [
                'name' => 'Los Angeles Temperature',
                'slug' => 'los-angeles-temperature',
                'description' => 'Temperature forecasts for Los Angeles, California',
                'location' => 'Los Angeles, CA',
                'event_prefix' => 'KXHIGHLAX'
            ],
            // Add more default categories as needed
        ];
        
        foreach ($defaultCategories as $category) {
            KalshiWeatherCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
    
    /**
     * Get all event tickers based on categories
     *
     * @return array
     */
    public function getAllWeatherEventTickers(): array
    {
        $tickers = [];
        $categories = KalshiWeatherCategory::all();
        
        // For each category, we'll add event tickers for today and next 2 days
        foreach ($categories as $category) {
            if (!$category->event_prefix) {
                continue;
            }
            
            // Get today and next 2 days
            for ($i = 0; $i < 3; $i++) {
                $date = now('America/Chicago')->addDays($i);
                // Format like 25APR13
                $dateStr = $date->format('y') . strtoupper($date->format('M')) . $date->format('d');
                $tickers[] = [
                    'ticker' => "{$category->event_prefix}-{$dateStr}",
                    'category_id' => $category->id,
                ];
            }
        }
        
        return $tickers;
    }
    
    /**
     * Find the category ID for an event ticker
     *
     * @param string $eventTicker
     * @return int|null
     */
    public function findCategoryForEventTicker(string $eventTicker): ?int
    {
        // Extract the prefix part before the dash
        $parts = explode('-', $eventTicker);
        if (empty($parts[0])) {
            return null;
        }
        
        $prefix = $parts[0];
        
        // Find category with matching event_prefix
        $category = KalshiWeatherCategory::where('event_prefix', $prefix)->first();
        
        return $category ? $category->id : null;
    }

    /**
     * Fetch markets for a specific date range using series_ticker
     *
     * @param string $seriesTicker
     * @param string $settleDateMin
     * @param string $settleDateMax
     * @return array
     */
    public function getMarketsByDate(string $seriesTicker, string $settleDateMin, string $settleDateMax): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/markets", [
                'series_ticker' => $seriesTicker,
                'settle_date_min' => $settleDateMin,
                'settle_date_max' => $settleDateMax,
            ]);
            
            if ($response->successful()) {
                return $response->json() ?? [];
            }
            
            Log::error('Kalshi API error - getMarketsByDate', [
                'status' => $response->status(),
                'body' => $response->body(),
                'series_ticker' => $seriesTicker,
                'settle_date_min' => $settleDateMin,
                'settle_date_max' => $settleDateMax
            ]);
            
            return [];
        } catch (\Exception $e) {
            Log::error('Kalshi API exception - getMarketsByDate', [
                'message' => $e->getMessage(),
                'series_ticker' => $seriesTicker,
                'settle_date_min' => $settleDateMin,
                'settle_date_max' => $settleDateMax
            ]);
            
            return [];
        }
    }

    /**
     * Fetch candlestick data for a specific market
     *
     * @param string $seriesTicker
     * @param string $marketTicker
     * @param int $startTs
     * @param int $endTs
     * @param int $periodInterval
     * @return array
     */
    public function getCandlesticks(string $seriesTicker, string $marketTicker, int $startTs, int $endTs, int $periodInterval = 60): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/series/{$seriesTicker}/markets/{$marketTicker}/candlesticks", [
                'start_ts' => $startTs,
                'end_ts' => $endTs,
                'period_interval' => $periodInterval,
            ]);
            
            if ($response->successful()) {
                return $response->json() ?? [];
            }
            
            Log::error('Kalshi API error - getCandlesticks', [
                'status' => $response->status(),
                'body' => $response->body(),
                'series_ticker' => $seriesTicker,
                'market_ticker' => $marketTicker,
                'start_ts' => $startTs,
                'end_ts' => $endTs
            ]);
            
            return [];
        } catch (\Exception $e) {
            Log::error('Kalshi API exception - getCandlesticks', [
                'message' => $e->getMessage(),
                'series_ticker' => $seriesTicker,
                'market_ticker' => $marketTicker,
                'start_ts' => $startTs,
                'end_ts' => $endTs
            ]);
            
            return [];
        }
    }
} 