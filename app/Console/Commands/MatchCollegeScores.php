<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Game;
use App\Models\Score;
use App\Models\Sport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class MatchCollegeScores extends Command
{
    protected $signature = 'match:college-scores {--debug}';
    protected $description = 'Match college football scores from API to existing games';

    private $unmatched = [];
    private $matched = [];

    // Common team name variations to help with matching
    private $teamMappings = [
        'louisiana' => ['ul lafayette', 'louisiana lafayette', 'louisiana ragin cajuns'],
        'lsu' => ['louisiana state'],
        'ole miss' => ['mississippi'],
        'pitt' => ['pittsburgh'],
        'usc' => ['southern california'],
        'ucf' => ['central florida'],
        'fau' => ['florida atlantic'],
        'fiu' => ['florida international'],
        'umass' => ['massachusetts'],
        'uconn' => ['connecticut'],
        'sjsu' => ['san jose state'],
        'smu' => ['southern methodist'],
        'tcu' => ['texas christian'],
        // Add more mappings as needed
    ];

    public function handle()
    {
        // Get API key from config
        $apiKey = Config::get('services.college_scores_api_key');

        if (!$apiKey) {
            $this->error("API key not found in configuration");
            return;
        }

        $sport = Sport::where('key', 'americanfootball_ncaaf')->first();
        if (!$sport) {
            $this->error("Sport not found");
            return;
        }

        // Get all unmatched games
        $games = Game::with(['homeTeam', 'awayTeam'])
            ->where('sport_id', $sport->id)
            ->where('commence_time', '<', now())
            ->whereDoesntHave('scores', function($query) {
                $query->where('period', 'F');
            })
            ->get();

        $this->info("Found {$games->count()} unmatched games");

        // Group games by year for efficient API calls
        $gamesByYear = $games->groupBy(function($game) {
            return Carbon::parse($game->commence_time)->year;
        });

        foreach ($gamesByYear as $year => $yearGames) {
            $this->info("Processing games from {$year}");

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'accept' => 'application/json',
            ])->get("https://api.collegefootballdata.com/games", [
                'year' => $year,
                'seasonType' => 'regular'
            ]);

            if ($response->failed()) {
                $this->error("Failed to fetch data for {$year}");
                continue;
            }

            $apiGames = $response->json();
            $this->matchGames($yearGames, $apiGames);
        }

        // Output summary
        $this->info("\nMatching Summary:");
        $this->info("Successfully matched: " . count($this->matched));
        $this->info("Unmatched games: " . count($this->unmatched));

        if ($this->option('debug') && count($this->unmatched) > 0) {
            $this->info("\nUnmatched games:");
            foreach ($this->unmatched as $game) {
                $this->warn("{$game['home']} vs {$game['away']} on {$game['date']}");
            }
        }
    }

    private function matchGames($dbGames, $apiGames)
    {
        foreach ($dbGames as $dbGame) {
            $homeTeam = $this->normalizeTeamName($dbGame->homeTeam->name);
            $awayTeam = $this->normalizeTeamName($dbGame->awayTeam->name);
            $gameDate = Carbon::parse($dbGame->commence_time);

            // Find matching game in API data
            $matchedGame = collect($apiGames)->first(function($apiGame) use ($homeTeam, $awayTeam, $gameDate) {
                $apiHomeTeam = $this->normalizeTeamName($apiGame['home_team']);
                $apiAwayTeam = $this->normalizeTeamName($apiGame['away_team']);
                $apiDate = Carbon::parse($apiGame['start_date']);

                // Try direct match first
                $directMatch = (
                    ($apiHomeTeam === $homeTeam && $apiAwayTeam === $awayTeam) ||
                    ($apiHomeTeam === $awayTeam && $apiAwayTeam === $homeTeam)
                );

                if ($directMatch) {
                    // For direct matches, allow a larger time window
                    return $apiDate->diffInHours($gameDate) <= 48;
                }

                // Try fuzzy matching if no direct match
                $fuzzyMatch = (
                    ($this->isFuzzyMatch($apiHomeTeam, $homeTeam) && $this->isFuzzyMatch($apiAwayTeam, $awayTeam)) ||
                    ($this->isFuzzyMatch($apiHomeTeam, $awayTeam) && $this->isFuzzyMatch($apiAwayTeam, $homeTeam))
                );

                // For fuzzy matches, use a stricter time window
                return $fuzzyMatch && $apiDate->diffInHours($gameDate) <= 24;
            });

            if ($matchedGame) {
                try {
                    Score::create([
                        'game_id' => $dbGame->id,
                        'home_score' => $matchedGame['home_points'],
                        'away_score' => $matchedGame['away_points'],
                        'period' => 'F'
                    ]);

                    $this->matched[] = [
                        'home' => $dbGame->homeTeam->name,
                        'away' => $dbGame->awayTeam->name,
                        'date' => $dbGame->commence_time
                    ];

                    if ($this->option('debug')) {
                        $this->info("Matched: {$dbGame->homeTeam->name} vs {$dbGame->awayTeam->name}");
                        $this->info("Score: {$matchedGame['home_points']} - {$matchedGame['away_points']}");
                    }
                } catch (\Exception $e) {
                    $this->error("Error creating score: " . $e->getMessage());
                }
            } else {
                $this->unmatched[] = [
                    'home' => $dbGame->homeTeam->name,
                    'away' => $dbGame->awayTeam->name,
                    'date' => $dbGame->commence_time
                ];

                if ($this->option('debug')) {
                    $this->warn("No match found for: {$dbGame->homeTeam->name} vs {$dbGame->awayTeam->name}");
                }
            }
        }
    }

    private function normalizeTeamName($name)
    {
        // Convert to lowercase and remove special characters
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9\s]/', '', $name);

        // Check for known mappings
        foreach ($this->teamMappings as $normalized => $variations) {
            if (in_array($name, $variations)) {
                return $normalized;
            }
        }

        // Remove common words and suffixes
        $commonWords = [
            'university', 'college', 'state', 'tech', 'institute',
            'northern', 'southern', 'eastern', 'western',
            'north', 'south', 'east', 'west', 'central',
            'agricultural', 'mechanical', 'am',
            'the', 'of', 'at', 'and'
        ];

        $words = explode(' ', $name);
        $words = array_diff($words, $commonWords);

        return implode(' ', $words);
    }

    private function isFuzzyMatch($str1, $str2)
    {
        // Convert both strings to lowercase and remove special characters
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        // Direct match
        if ($str1 === $str2) {
            return true;
        }

        // Check if one string contains the other
        if (strpos($str1, $str2) !== false || strpos($str2, $str1) !== false) {
            return true;
        }

        // Calculate similarity
        similar_text($str1, $str2, $percent);
        return $percent >= 80; // Require 80% similarity for a match
    }
}
