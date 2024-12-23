<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OddsApiService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('ODDS_API_KEY');
    }

    public function getHistoricalOdds($sportKey, $date)
    {
        try {
            $isoDate = Carbon::parse($date)->toIso8601ZuluString();

            $url = "https://api.the-odds-api.com/v4/sports/{$sportKey}/odds-history";

            $response = Http::accept('application/json')
                ->get($url, [
                    'apiKey' => $this->apiKey,
                    'regions' => 'us',
                    'markets' => 'spreads',
                    'oddsFormat' => 'american',
                    'date' => $isoDate
                ]);

            if ($response->failed()) {
                throw new \Exception('Odds API request failed: ' . $response->body());
            }

            $responseData = $response->json();

            // Check if we have the data key and it's an array
            if (!isset($responseData['data']) || !is_array($responseData['data'])) {
                Log::warning('No games data in response', [
                    'response' => $responseData,
                    'date' => $date
                ]);
                return [];
            }

            return $responseData['data'];
        } catch (\Exception $e) {
            Log::error('Error in OddsApiService: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getCurrentOdds($sportKey, $markets = 'spreads')
    {
        try {
            $url = "https://api.the-odds-api.com/v4/sports/{$sportKey}/odds";

            $response = Http::accept('application/json')
                ->get($url, [
                    'apiKey' => $this->apiKey,
                    'regions' => 'us',
                    'markets' => $markets,
                    'oddsFormat' => 'american'
                ]);

            if ($response->failed()) {
                throw new \Exception('Odds API request failed: ' . $response->body());
            }

            $responseData = $response->json();

            // For current odds, the response is directly an array of games
            if (!is_array($responseData)) {
                Log::warning('Invalid response format for current odds', [
                    'response' => $responseData
                ]);
                return [];
            }

            return $responseData;
        } catch (\Exception $e) {
            Log::error('Error in OddsApiService: ' . $e->getMessage(), [
                'sport_key' => $sportKey,
                'markets' => $markets
            ]);
            throw $e;
        }
    }

    public function getSports()
    {
        try {
            $response = Http::accept('application/json')
                ->get('https://api.the-odds-api.com/v4/sports', [
                    'apiKey' => $this->apiKey
                ]);

            if ($response->failed()) {
                throw new \Exception('Sports API request failed: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error fetching sports: ' . $e->getMessage());
            throw $e;
        }
    }
}
