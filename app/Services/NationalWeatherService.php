<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NationalWeatherService
{
    protected $baseUrl = 'https://api.weather.gov';
    protected $gridPoints = [
        'Chicago' => ['office' => 'LOT', 'gridX' => 70, 'gridY' => 73], // Chicago O'Hare
        'Austin' => ['office' => 'EWX', 'gridX' => 155, 'gridY' => 91], // Austin-Bergstrom
        'Denver' => ['office' => 'BOU', 'gridX' => 84, 'gridY' => 65], // Denver International
        'Los Angeles' => ['office' => 'LOX', 'gridX' => 116, 'gridY' => 66], // Los Angeles International
        'New York' => ['office' => 'OKX', 'gridX' => 32, 'gridY' => 34], // Central Park
        'Philadelphia' => ['office' => 'PHI', 'gridX' => 85, 'gridY' => 67], // Philadelphia International
        'Miami' => ['office' => 'MFL', 'gridX' => 105, 'gridY' => 70], // Miami International
    ];

    /**
     * Get hourly forecast for a location and date
     *
     * @param string $location The location name (must match a key in $gridPoints)
     * @param Carbon $date The target date
     * @return array|null Array with hourly forecasts, or null if not found
     */
    public function getHourlyForecast(string $location, Carbon $date): ?array
    {
        $gridPoint = $this->gridPoints[$location] ?? null;
        if (!$gridPoint) {
            Log::error("No grid point found for {$location}");
            return null;
        }

        try {
            // Get hourly forecast
            $url = "{$this->baseUrl}/gridpoints/{$gridPoint['office']}/{$gridPoint['gridX']},{$gridPoint['gridY']}/forecast/hourly";
            $response = Http::withHeaders([
                'User-Agent' => 'OddsTracker/1.0 (ben@example.com)'
            ])->get($url);

            if (!$response->successful()) {
                Log::error("NWS API error", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'location' => $location,
                    'url' => $url
                ]);
                return null;
            }

            $data = $response->json();
            
            // Debug log the raw response
            Log::info("NWS API raw response", [
                'url' => $url,
                'status' => $response->status(),
                'raw_response' => $response->body(),
                'parsed_data' => $data
            ]);

            if (!isset($data['properties']['periods']) || !is_array($data['properties']['periods'])) {
                Log::error("NWS API response missing periods", [
                    'data' => $data,
                    'location' => $location
                ]);
                return null;
            }

            $forecasts = [];
            foreach ($data['properties']['periods'] as $period) {
                $forecastTime = Carbon::parse($period['startTime']);
                if ($forecastTime->isSameDay($date)) {
                    $forecasts[] = [
                        'time' => $forecastTime,
                        'temperature' => (int)$period['temperature'],
                        'wind_speed' => $period['windSpeed'],
                        'wind_direction' => $period['windDirection'],
                        'short_forecast' => $period['shortForecast'],
                        'detailed_forecast' => $period['detailedForecast']
                    ];
                }
            }

            return $forecasts;
        } catch (\Exception $e) {
            Log::error("Error fetching NWS forecast", [
                'message' => $e->getMessage(),
                'location' => $location,
                'date' => $date->format('Y-m-d')
            ]);
            return null;
        }
    }

    /**
     * Get the daily high temperature forecast for a location and date
     *
     * @param string $location The location name
     * @param Carbon $date The target date
     * @return array|null Array with high_temperature and low_temperature, or null if not found
     */
    public function getDailyForecast(string $location, Carbon $date): ?array
    {
        $gridPoint = $this->gridPoints[$location] ?? null;
        if (!$gridPoint) {
            Log::error("No grid point found for {$location}");
            return null;
        }

        try {
            // Get daily forecast
            $url = "{$this->baseUrl}/gridpoints/{$gridPoint['office']}/{$gridPoint['gridX']},{$gridPoint['gridY']}/forecast";
            $response = Http::withHeaders([
                'User-Agent' => 'OddsTracker/1.0 (ben@example.com)'
            ])->get($url);

            if (!$response->successful()) {
                Log::error("NWS API error", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'location' => $location,
                    'url' => $url
                ]);
                return null;
            }

            $data = $response->json();
            
            // Debug log the raw response
            Log::info("NWS API raw response", [
                'url' => $url,
                'status' => $response->status(),
                'raw_response' => $response->body(),
                'parsed_data' => $data
            ]);

            if (!isset($data['properties']['periods']) || !is_array($data['properties']['periods'])) {
                Log::error("NWS API response missing periods", [
                    'data' => $data,
                    'location' => $location
                ]);
                return null;
            }

            $highTemp = null;
            $lowTemp = null;

            foreach ($data['properties']['periods'] as $period) {
                $forecastTime = Carbon::parse($period['startTime']);
                if ($forecastTime->isSameDay($date)) {
                    $temp = (int)$period['temperature'];
                    if ($highTemp === null || $temp > $highTemp) {
                        $highTemp = $temp;
                    }
                    if ($lowTemp === null || $temp < $lowTemp) {
                        $lowTemp = $temp;
                    }
                }
            }

            if ($highTemp === null || $lowTemp === null) {
                Log::warning("Could not find both high and low temperatures for {$location} on {$date->format('Y-m-d')}", [
                    'high_temp' => $highTemp,
                    'low_temp' => $lowTemp
                ]);
                return null;
            }

            return [
                'high_temperature' => $highTemp,
                'low_temperature' => $lowTemp
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching NWS daily forecast", [
                'message' => $e->getMessage(),
                'location' => $location,
                'date' => $date->format('Y-m-d')
            ]);
            return null;
        }
    }
} 