<?php

namespace App\Services;

use GuzzleHttp\Client;
use DOMDocument;
use DOMXPath;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OddsService
{
    protected TeamNameMapper $teamMapper;

    public function __construct(TeamNameMapper $teamMapper)
    {
        $this->teamMapper = $teamMapper;
    }

    public function getNBAScores($date)
    {
        // Format date as YYYYMMDD without any separators
        $formattedDate = $date->format('Ymd');
        $url = "https://www.espn.com/nba/scoreboard/_/date/" . $formattedDate;
        
        try {
            $client = new Client();
            $response = $client->get($url);
            $html = $response->getBody()->getContents();
            
            // Create a new DOMDocument and load the HTML
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            
            $games = [];
            $matchedGames = [];
            $unmatchedGames = [];
            
            // Find all game containers
            $gameNodes = $xpath->query("//section[contains(@class, 'Scoreboard')]");
            
            foreach ($gameNodes as $gameNode) {
                // Get team names and scores
                $competitors = $xpath->query(".//li[contains(@class, 'ScoreboardScoreCell__Item')]", $gameNode);
                
                if ($competitors->length >= 2) {
                    $awayTeam = $xpath->query(".//div[contains(@class, 'ScoreCell__TeamName')]", $competitors->item(0))->item(0);
                    $homeTeam = $xpath->query(".//div[contains(@class, 'ScoreCell__TeamName')]", $competitors->item(1))->item(0);
                    
                    $awayScore = $xpath->query(".//div[contains(@class, 'ScoreCell__Score')]", $competitors->item(0))->item(0);
                    $homeScore = $xpath->query(".//div[contains(@class, 'ScoreCell__Score')]", $competitors->item(1))->item(0);
                    
                    $status = $xpath->query(".//div[contains(@class, 'ScoreCell__Time')]", $gameNode)->item(0);
                    
                    $gameData = [
                        'away_team' => $awayTeam ? trim($awayTeam->textContent) : '',
                        'away_score' => $awayScore ? trim($awayScore->textContent) : '',
                        'home_team' => $homeTeam ? trim($homeTeam->textContent) : '',
                        'home_score' => $homeScore ? trim($homeScore->textContent) : '',
                        'status' => $status ? trim($status->textContent) : 'Unknown',
                        'game_date' => $date->format('Y-m-d')
                    ];
                    
                    // Try to match the game in our database
                    $game = $this->findMatchingGame($gameData);
                    
                    if ($game) {
                        $matchedGames[] = [
                            'game' => $game,
                            'data' => $gameData
                        ];
                    } else {
                        $unmatchedGames[] = $gameData;
                    }
                    
                    $games[] = $gameData;
                }
            }
            
            // Update scores for matched games
            foreach ($matchedGames as $matchedGame) {
                $this->updateGameScore($matchedGame['game'], $matchedGame['data']);
            }
            
            return [
                'status' => 'success',
                'message' => 'NBA scores retrieved successfully',
                'data' => $games,
                'matched_count' => count($matchedGames),
                'unmatched_count' => count($unmatchedGames),
                'unmatched_games' => $unmatchedGames
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error fetching NBA scores: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    protected function findMatchingGame($gameData)
    {
        // Map ESPN team names to our team names
        $homeTeamName = $this->teamMapper->getTeamName($gameData['home_team']);
        $awayTeamName = $this->teamMapper->getTeamName($gameData['away_team']);

        if (!$homeTeamName || !$awayTeamName) {
            Log::info('Unable to map team names:', [
                'espn_home_team' => $gameData['home_team'],
                'espn_away_team' => $gameData['away_team'],
                'mapped_home_team' => $homeTeamName,
                'mapped_away_team' => $awayTeamName,
                'date' => $gameData['game_date']
            ]);
            return null;
        }

        // Find teams by mapped names
        $homeTeam = \App\Models\Team::where('name', 'LIKE', $homeTeamName)->first();
        $awayTeam = \App\Models\Team::where('name', 'LIKE', $awayTeamName)->first();

        if (!$homeTeam || !$awayTeam) {
            Log::info('Teams not found in database:', [
                'home_team' => $homeTeamName,
                'away_team' => $awayTeamName,
                'mapped_home_team_found' => $homeTeam ? 'yes' : 'no',
                'mapped_away_team_found' => $awayTeam ? 'yes' : 'no',
                'date' => $gameData['game_date']
            ]);
            return null;
        }

        // Create a Carbon instance for the game date at start of day
        $gameDate = Carbon::parse($gameData['game_date'])->startOfDay();
        
        // Create a window from the day before to the day after
        $startDate = $gameDate->copy()->subDay();
        $endDate = $gameDate->copy()->addDay()->endOfDay();
        
        // Query the database to find a matching game within the time window
        $game = \App\Models\Game::whereBetween('commence_time', [$startDate, $endDate])
            ->where(function($query) use ($homeTeam, $awayTeam) {
                $query->where(function($q) use ($homeTeam, $awayTeam) {
                    $q->where('home_team_id', $homeTeam->id)
                      ->where('away_team_id', $awayTeam->id);
                })
                ->orWhere(function($q) use ($homeTeam, $awayTeam) {
                    $q->where('home_team_id', $awayTeam->id)
                      ->where('away_team_id', $homeTeam->id);
                });
            })
            ->first();

        if (!$game) {
            Log::info('No game found in time window:', [
                'home_team' => $homeTeamName,
                'away_team' => $awayTeamName,
                'start_date' => $startDate->toDateTimeString(),
                'end_date' => $endDate->toDateTimeString(),
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id
            ]);
        }

        return $game;
    }

    protected function updateGameScore($game, $gameData)
    {
        // Only update if the game is finished
        if (strtolower($gameData['status']) === 'final') {
            // Check if teams are reversed
            $teamsReversed = (
                $game->home_team_id === $this->findTeamId($gameData['away_team']) &&
                $game->away_team_id === $this->findTeamId($gameData['home_team'])
            );
            
            $score = new \App\Models\Score([
                'game_id' => $game->id,
                'home_score' => $teamsReversed ? $gameData['away_score'] : $gameData['home_score'],
                'away_score' => $teamsReversed ? $gameData['home_score'] : $gameData['away_score'],
                'status' => $gameData['status']
            ]);
            
            $score->save();
            
            // Update game status if needed
            $game->status = 'completed';
            $game->save();
        }
    }

    protected function findTeamId($teamName)
    {
        $mappedName = $this->teamMapper->getTeamName($teamName);
        if (!$mappedName) {
            return null;
        }
        
        $team = \App\Models\Team::where('name', 'LIKE', $mappedName)->first();
        return $team ? $team->id : null;
    }
} 