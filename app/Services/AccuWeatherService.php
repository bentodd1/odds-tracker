<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AccuWeatherService
{
    protected $apiKey;
    protected $baseUrl = 'http://dataservice.accuweather.com';

    protected $locationKeys = [
        'Austin' => '351193',      // Austin-Bergstrom International Airport (AUS)
        'Denver' => '2626644',     // Denver International Airport (DEN)
        'Chicago' => '2626577',    // Chicago O'Hare International Airport (ORD)
        'Los Angeles' => '2142541', // Los Angeles International Airport (LAX)
        'New York' => '2627448',   // New York (Central Park)
        'Philadelphia' => '350540', // Philadelphia International Airport (PHL)
        'Miami' => '347936'        // Miami International Airport (MIA)
    ];

    public function __construct()
    {
        $this->apiKey = env('ACCUWEATHER_API_KEY');
        if (!$this->apiKey) {
            throw new \RuntimeException('AccuWeather API key not found in .env file.');
        }
    }

    /**
     * Get daily forecast for a location and date
     *
     * @param string $location The location name (must match a key in $locationKeys)
     * @param Carbon $date The target date
     * @return array|null Array with high_temperature and low_temperature, or null if not found
     */
    public function getDailyForecast(string $location, Carbon $date): ?array
    {
        $locationKey = $this->locationKeys[$location] ?? null;
        if (!$locationKey) {
            Log::error("No location key found for {$location}");
            return null;
        }

        try {
            // Get 5-day forecast
            $url = "{$this->baseUrl}/forecasts/v1/daily/5day/{$locationKey}";
            $response = Http::get($url, [
                'apikey' => $this->apiKey,
                'details' => false,
                'metric' => false
            ]);

            if (!$response->successful()) {
                Log::error("AccuWeather API error", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'location' => $location
                ]);
                return null;
            }

            $data = $response->json();
            if (!isset($data['DailyForecasts']) || !is_array($data['DailyForecasts'])) {
                return null;
            }

            // Find the forecast for our target date
            foreach ($data['DailyForecasts'] as $forecast) {
                $forecastDate = Carbon::parse($forecast['Date']);
                if ($forecastDate->isSameDay($date)) {
                    return [
                        'high_temperature' => (int)$forecast['Temperature']['Maximum']['Value'],
                        'low_temperature' => (int)$forecast['Temperature']['Minimum']['Value']
                    ];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Error fetching AccuWeather forecast", [
                'message' => $e->getMessage(),
                'location' => $location,
                'date' => $date->format('Y-m-d')
            ]);
            return null;
        }
    }
} 