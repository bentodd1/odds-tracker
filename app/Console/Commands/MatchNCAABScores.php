<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Game;
use App\Models\Score;
use App\Models\Sport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\File;

class MatchNCAABScores extends Command
{
    protected $signature = 'match:ncaab-scores 
        {--date= : The date to fetch scores for (YYYY-MM-DD format)}
        {--debug : Show debug information}';
    protected $description = 'Match NCAA basketball scores from NCAA.com to existing games';

    private $unmatched = [];
    private $matched = [];

    // Common team name variations to help with matching
    private $teamMappings = [
        // Common abbreviations
        'usc' => ['southern california', 'southern cal'],
        'ucf' => ['central florida', 'cent florida'],
        'uconn' => ['connecticut'],
        'umass' => ['massachusetts'],
        'smu' => ['southern methodist'],
        'tcu' => ['texas christian'],
        'ucla' => ['california los angeles'],
        'unlv' => ['nevada las vegas'],
        'utep' => ['texas el paso'],
        'unc' => ['north carolina'],
        'ole miss' => ['mississippi'],
        'nc state' => ['north carolina state'],
        
        // Saint/St. variations
        'saint johns' => ['st johns', 'st. johns', 'st. john\'s', 'st johns red storm'],
        'saint marys' => ['st marys', 'st. marys', 'saint marys gaels'],
        'saint louis' => ['st louis', 'st. louis', 'saint louis billikens'],
        'saint josephs' => ['st josephs', 'st. josephs', 'saint josephs hawks'],
        'saint peters' => ['st peters', 'st. peters'],
        'saint bonaventure' => ['st bonaventure', 'st. bonaventure'],
        'saint francis' => ['st francis', 'st. francis'],
        'saint thomas' => ['st thomas', 'st. thomas'],
        
        // Common suffixes to remove
        'blue devils' => ['duke'],
        'crimson tide' => ['alabama'],
        'volunteers' => ['tennessee'],
        'tigers' => ['memphis', 'missouri', 'auburn', 'clemson', 'lsu'],
        'wildcats' => ['kentucky', 'arizona', 'kansas state', 'villanova'],
        'bulldogs' => ['gonzaga', 'butler', 'georgia', 'mississippi state'],
        'cardinals' => ['louisville', 'stanford'],
        'bears' => ['baylor', 'california'],
        'hoyas' => ['georgetown'],
        'jayhawks' => ['kansas'],
        'tar heels' => ['north carolina'],
        'wolfpack' => ['nc state'],
        'orange' => ['syracuse'],
        'spartans' => ['michigan state'],
        'wolverines' => ['michigan'],
        'buckeyes' => ['ohio state'],
        'boilermakers' => ['purdue'],
        'hoosiers' => ['indiana'],
        'hawkeyes' => ['iowa'],
        'cyclones' => ['iowa state'],
        'razorbacks' => ['arkansas'],
        'gators' => ['florida'],
        'seminoles' => ['florida state'],
        'hurricanes' => ['miami fl', 'miami florida'],
        'cavaliers' => ['virginia'],
        'hokies' => ['virginia tech'],
        
        // State variations
        'mississippi state' => ['miss state', 'miss st'],
        'michigan state' => ['mich state', 'mich st'],
        'florida state' => ['fla state', 'fla st'],
        'kansas state' => ['kan state', 'kan st', 'k-state'],
        'arizona state' => ['asu', 'ariz state', 'ariz st'],
        'arkansas state' => ['ark state', 'ark st'],
        'louisiana state' => ['lsu', 'la state'],
        'ohio state' => ['osu'],
        'oklahoma state' => ['okla state', 'okla st', 'ok state'],
        'oregon state' => ['ore state', 'ore st'],
        
        // Directional schools
        'north carolina' => ['unc', 'n carolina', 'n.c.'],
        'south carolina' => ['s carolina', 's.c.'],
        'west virginia' => ['w virginia', 'w.v.'],
        'east carolina' => ['e carolina', 'e.c.'],
        'northern illinois' => ['n illinois', 'n. illinois'],
        'southern illinois' => ['s illinois', 's. illinois'],
        'western kentucky' => ['w kentucky', 'w. kentucky'],
        'eastern kentucky' => ['e kentucky', 'e. kentucky'],
        
        // Other common variations
        'miami fl' => ['miami florida', 'miami (fl)', 'miami (fla)'],
        'miami oh' => ['miami ohio', 'miami (oh)', 'miami (ohio)'],
        'texas am' => ['texas a&m', 'texas a & m'],
        'bowling green' => ['bowling green state', 'bgsu'],
        'brigham young' => ['byu'],
        'central michigan' => ['cent michigan', 'cmu'],
        'cincinnati' => ['cincy'],
        'colorado state' => ['colo state', 'colo st'],
        'depaul' => ['de paul'],
        'detroit mercy' => ['detroit'],
        'illinois chicago' => ['uic'],
        'loyola chicago' => ['loyola il', 'loyola (il)'],
        'loyola marymount' => ['lmu'],
        'massachusetts lowell' => ['umass lowell'],
        'middle tennessee' => ['middle tenn', 'mtsu'],
        'mississippi valley state' => ['mvsu'],
        'missouri kansas city' => ['umkc'],
        'montana state' => ['mont state', 'mont st'],
        'nevada las vegas' => ['unlv'],
        'new mexico state' => ['nm state', 'nmsu'],
        'north carolina at' => ['nc at', 'north carolina a&t'],
        'north carolina central' => ['nc central'],
        'north dakota state' => ['ndsu'],
        'south dakota state' => ['sdsu'],
    ];

    public function handle()
    {
        $sport = Sport::where('key', 'basketball_ncaab')->first();
        if (!$sport) {
            $this->error("Sport not found");
            return;
        }

        // Default to yesterday if no date provided
        $targetDate = $this->option('date') 
            ? Carbon::parse($this->option('date'))
            : now()->subDay();

        $this->log("Processing date: " . $targetDate->format('Y/m/d'));

        $dates = [
            $targetDate->format('Y/m/d'),
            $targetDate->copy()->subDay()->format('Y/m/d')
        ];

        $this->log("Processing dates: " . implode(', ', $dates));
        
        $allScores = [];
        foreach ($dates as $date) {
            $this->log("\nFetching scores for {$date}");
            
            try {
                $url = "https://www.ncaa.com/scoreboard/basketball-men/d1/{$date}/all-conf";
                $this->log("URL: {$url}");
                
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ])->get($url);

                if ($response->failed()) {
                    $this->log("Failed to fetch scores for {$date}", 'error');
                    continue;
                }

                $doc = new DOMDocument();
                @$doc->loadHTML($response->body());
                $xpath = new DOMXPath($doc);

                // Save raw HTML for debugging
                if ($this->option('debug')) {
                    $safeDateStr = str_replace('/', '-', $date);
                    $debugPath = storage_path("logs/ncaab-raw-{$safeDateStr}.html");
                    
                    // Ensure the directory exists
                    $directory = dirname($debugPath);
                    if (!File::exists($directory)) {
                        File::makeDirectory($directory, 0755, true);
                    }
                    
                    File::put($debugPath, $response->body());
                    $this->log("Saved raw HTML to: {$debugPath}");
                }

                $gameNodes = $xpath->query("//div[contains(@class, 'gamePod-type-game')]");
                $this->log("Found " . $gameNodes->length . " games for {$date}");

                foreach ($gameNodes as $index => $gameNode) {
                    $homeTeamNode = $xpath->query(".//li[last()]//span[contains(@class, 'gamePod-game-team-name') and not(contains(@class, 'short'))]", $gameNode)->item(0);
                    $awayTeamNode = $xpath->query(".//li[1]//span[contains(@class, 'gamePod-game-team-name') and not(contains(@class, 'short'))]", $gameNode)->item(0);
                    $homeScoreNode = $xpath->query(".//li[last()]//span[contains(@class, 'gamePod-game-team-score')]", $gameNode)->item(0);
                    $awayScoreNode = $xpath->query(".//li[1]//span[contains(@class, 'gamePod-game-team-score')]", $gameNode)->item(0);
                    $statusNode = $xpath->query(".//div[contains(@class, 'gamePod-status')]", $gameNode)->item(0);

                    $this->log(sprintf("Game %d:", ($index + 1)));
                    $this->log("Home Team: " . ($homeTeamNode?->textContent ?? 'Not found'));
                    $this->log("Away Team: " . ($awayTeamNode?->textContent ?? 'Not found'));
                    $this->log("Home Score: " . ($homeScoreNode?->textContent ?? 'Not found'));
                    $this->log("Away Score: " . ($awayScoreNode?->textContent ?? 'Not found'));
                    $this->log("Status: " . ($statusNode?->textContent ?? 'Not found'));

                    if (!$homeTeamNode || !$awayTeamNode || !$homeScoreNode || !$awayScoreNode || !$statusNode) {
                        $this->log("Skipping game due to missing data", 'error');
                        continue;
                    }

                    // Only store final scores
                    if (!str_contains(strtolower($statusNode->textContent), 'final')) {
                        $this->log("Skipping non-final game: {$statusNode->textContent}");
                        continue;
                    }

                    $allScores[] = [
                        'home_team' => trim($homeTeamNode->textContent),
                        'away_team' => trim($awayTeamNode->textContent),
                        'home_score' => (int)trim($homeScoreNode->textContent),
                        'away_score' => (int)trim($awayScoreNode->textContent),
                        'date' => $date
                    ];

                    $this->log("Added final score to processing list");
                }

            } catch (\Exception $e) {
                $this->log("Error processing {$date}: " . $e->getMessage(), 'error');
                $this->log($e->getTraceAsString(), 'error');
            }
        }

        $this->log("\nFound " . count($allScores) . " final scores to process");

        // Now try to match these scores to games in our database
        foreach ($allScores as $score) {
            $games = Game::with(['homeTeam', 'awayTeam'])
                ->where('sport_id', $sport->id)
                ->where('commence_time', '>', now()->subHours(48))
                ->where('commence_time', '<', now()->addHours(24))
                ->whereDoesntHave('scores', function($query) {
                    $query->where('period', 'F');
                })
                ->get();

            foreach ($games as $game) {
                if ($this->teamsMatch($game, $score['home_team'], $score['away_team'])) {
                    try {
                        Score::create([
                            'game_id' => $game->id,
                            'home_score' => $score['home_score'],
                            'away_score' => $score['away_score'],
                            'period' => 'F'
                        ]);

                        $this->matched[] = [
                            'home' => $game->homeTeam->name,
                            'away' => $game->awayTeam->name,
                            'date' => $game->commence_time,
                            'score' => "{$score['away_score']}-{$score['home_score']}"
                        ];

                        if ($this->option('debug')) {
                            $this->info("Matched and recorded score: {$game->awayTeam->name} {$score['away_score']} @ {$game->homeTeam->name} {$score['home_score']}");
                        }
                        break;
                    } catch (\Exception $e) {
                        $this->error("Error saving score: " . $e->getMessage());
                    }
                }
            }
        }

        // Output summary
        $this->info("\nMatching Summary:");
        $this->info("Successfully matched: " . count($this->matched));
        
        if ($this->option('debug') && count($this->matched) > 0) {
            $this->info("\nMatched games:");
            foreach ($this->matched as $game) {
                $this->info("{$game['away']} @ {$game['home']} ({$game['score']})");
            }
        }
    }

    private function teamsMatch($game, $homeTeam, $awayTeam)
    {
        if ($this->option('debug')) {
            $this->info("\nAttempting to match teams:");
            $this->info("NCAA Home Team: " . $homeTeam);
            $this->info("NCAA Away Team: " . $awayTeam);
            $this->info("DB Home Team: " . $game->homeTeam->name);
            $this->info("DB Away Team: " . $game->awayTeam->name);
        }

        $dbHomeTeam = $this->normalizeTeamName($game->homeTeam->name);
        $dbAwayTeam = $this->normalizeTeamName($game->awayTeam->name);
        $ncaaHomeTeam = $this->normalizeTeamName($homeTeam);
        $ncaaAwayTeam = $this->normalizeTeamName($awayTeam);

        if ($this->option('debug')) {
            $this->info("After normalization:");
            $this->info("NCAA Home (normalized): " . $ncaaHomeTeam);
            $this->info("NCAA Away (normalized): " . $ncaaAwayTeam);
            $this->info("DB Home (normalized): " . $dbHomeTeam);
            $this->info("DB Away (normalized): " . $dbAwayTeam);
        }

        // Try direct match first
        if (($ncaaHomeTeam === $dbHomeTeam && $ncaaAwayTeam === $dbAwayTeam) ||
            ($ncaaHomeTeam === $dbAwayTeam && $ncaaAwayTeam === $dbHomeTeam)) {
            if ($this->option('debug')) {
                $this->info("✓ Direct match found!");
            }
            return true;
        }

        // Try fuzzy matching
        $fuzzyMatch = ($this->isFuzzyMatch($ncaaHomeTeam, $dbHomeTeam) && 
                      $this->isFuzzyMatch($ncaaAwayTeam, $dbAwayTeam)) ||
                     ($this->isFuzzyMatch($ncaaHomeTeam, $dbAwayTeam) && 
                      $this->isFuzzyMatch($ncaaAwayTeam, $dbHomeTeam));

        if ($this->option('debug')) {
            if ($fuzzyMatch) {
                $this->info("✓ Fuzzy match found!");
            } else {
                $this->error("✗ No match found");
            }
        }

        return $fuzzyMatch;
    }

    private function normalizeTeamName($name)
    {
        if ($this->option('debug')) {
            $this->info("Normalizing name: " . $name);
        }

        $name = strtolower(trim($name));
        
        // Remove common suffixes for better matching
        $name = str_replace([' blue devils', ' crimson tide', ' volunteers', ' tigers', ' cyclones', ' cougars'], '', $name);
        
        // Normalize St./Saint/State variations
        $name = preg_replace('/\bSt\.\s+/', 'Saint ', $name);
        $name = preg_replace('/\bSt\s+/', 'Saint ', $name);
        $name = preg_replace('/\bState\b/', 'St', $name);

        if ($this->option('debug')) {
            $this->info("Result after normalization: " . $name);
        }

        return $name;
    }

    private function isFuzzyMatch($name1, $name2)
    {
        if ($this->option('debug')) {
            $this->info("Attempting fuzzy match between:");
            $this->info("  Name 1: " . $name1);
            $this->info("  Name 2: " . $name2);
        }

        // Consider names matching if one contains the other
        $match = str_contains($name1, $name2) || str_contains($name2, $name1);

        if ($this->option('debug')) {
            if ($match) {
                $this->info("  ✓ Fuzzy match successful");
            } else {
                $this->info("  ✗ Fuzzy match failed");
            }
        }

        return $match;
    }

    private function log($message, $type = 'info')
    {
        // Console output
        if ($this->option('debug')) {
            if ($type === 'error') {
                $this->error($message);
            } else {
                $this->info($message);
            }
        }

        // File logging
        $date = now()->format('Y-m-d');
        $logPath = storage_path("logs/ncaab-scores-{$date}.log");
        
        $timestamp = now()->format('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        File::append($logPath, $logMessage);
    }
} 