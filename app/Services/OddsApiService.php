<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Game;

class OddsApiService
{
    protected $apiKey;
    protected $region = 'eu';

    public function __construct()
    {
        $this->apiKey = env('ODDS_API_KEY');
    }

    public function setRegion($region)
    {
        $this->region = $region;
    }

    public function getHistoricalOdds($sportKey, $date, $markets = 'spreads', $region = null)
    {
        try {
            $isoDate = Carbon::parse($date)->toIso8601ZuluString();

            $url = "https://api.the-odds-api.com/v4/sports/{$sportKey}/odds-history";

            $response = Http::accept('application/json')
                ->get($url, [
                    'apiKey' => $this->apiKey,
                    'regions' => $region ?? $this->region,
                    'markets' => $markets,
                    'oddsFormat' => 'american',
                    'date' => $isoDate
                ]);

            if ($response->failed()) {
                throw new \Exception('Odds API request failed: ' . $response->body());
            }

            $responseData = $response->json();

            if (!isset($responseData['data']) || !is_array($responseData['data'])) {
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
                    'regions' => 'eu,us,us2,uk,us_ex',
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

    public function processGame($gameData, $sport, $homeTeam, $awayTeam)
    {
        $commenceTime = Carbon::parse($gameData['commence_time']);

        // Try to find an existing game by teams and commence time (±12 hours)
        $game = Game::where('sport_id', $sport->id)
            ->where('home_team_id', $homeTeam->id)
            ->where('away_team_id', $awayTeam->id)
            ->whereBetween('commence_time', [
                $commenceTime->copy()->subHours(12),
                $commenceTime->copy()->addHours(12)
            ])
            ->first();

        if ($game) {
            // Update the game if needed
            $game->update([
                'commence_time' => $commenceTime,
                'completed' => true,
                // ... any other fields you want to update
            ]);
        } else {
            // Create a new game
            $game = Game::create([
                'sport_id' => $sport->id,
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'commence_time' => $commenceTime,
                'season' => $gameData['season'] ?? null,
                'completed' => true,
                // 'game_id' => $gameData['id'], // Optionally store the Odds API id for reference
            ]);
        }
    }
}
